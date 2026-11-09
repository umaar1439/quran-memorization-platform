<?php

declare(strict_types=1);

namespace App\Actions;

use App\DTOs\CertificateData;
use App\Enums\CertificateStatus;
use App\Jobs\GenerateCertificateJob;
use App\Models\Certificate;
use App\Models\StudentProfile;
use App\Models\User;
use App\Support\FeatureFlagCache;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * ينفّذ إصدار شهادة بعد التحقق من feature flag (certificates.enabled)
 * وصلاحية certificates.generate + دور org-admin (§7، §3). يُنشئ سجل
 * Certificate بـ ULID، verification_code فريد، ملف PDF مبسّط في
 * Private Storage، وaudit_log كامل (§1، §4 تهديد #11، #16).
 */
class GenerateCertificateAction
{
    public function __construct(
        private readonly FeatureFlagCache $featureFlags,
    ) {}

    /**
     * الفحص الأول (دفاع الطبقة الأولى): الدور + الصلاحية + الـ feature flag.
     * الفحص الثاني يحدث داخل GenerateCertificateJob وقت التنفيذ الفعلي.
     */
    public function execute(CertificateData $data, User $actor): Certificate
    {
        if (! $actor->hasRole('org-admin') && ! $actor->hasRole('super-admin')) {
            throw ValidationException::withMessages([
                'actor' => __('messages.certificates.forbidden_role'),
            ]);
        }

        if (! $actor->can('certificates.generate')) {
            throw ValidationException::withMessages([
                'actor' => __('messages.certificates.forbidden_permission'),
            ]);
        }

        if (! $this->featureEnabled($actor->organization_id)) {
            throw ValidationException::withMessages([
                'feature' => __('messages.certificates.feature_disabled'),
            ]);
        }

        $student = StudentProfile::withoutGlobalScopes()->findOrFail($data->studentId);

        if ($actor->organization_id === null || $actor->organization_id !== $student->organization_id) {
            throw ValidationException::withMessages([
                'student_id' => __('messages.certificates.student_other_org'),
            ]);
        }

        $certificate = Certificate::withoutGlobalScopes()->create([
            'organization_id' => $student->organization_id,
            'student_id' => $student->id,
            'exam_id' => $data->examId,
            'certificate_type' => $data->certificateType,
            'issued_at' => now(),
            'issued_by' => $actor->id,
            'verification_code' => (string) Str::ulid(),
            'status' => CertificateStatus::Pending,
        ]);

        // إرسال التوليد الفعلي للملف إلى الطابور (Redis) — لا توليد متزامن.
        GenerateCertificateJob::dispatch($certificate->id, $actor->id);

        return $certificate;
    }

    private function featureEnabled(?int $organizationId): bool
    {
        return $this->featureFlags->enabled($organizationId, 'certificates.enabled');
    }
}
