<?php

namespace App\Actions;

use App\Enums\CertificateStatus;
use App\Jobs\GenerateCertificateJob;
use App\Models\Certificate;
use App\Models\FeatureFlag;
use App\Models\StudentProfile;
use App\Models\User;
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
    /**
     * الفحص الأول (دفاع الطبقة الأولى): الدور + الصلاحية + الـ feature flag.
     * الفحص الثاني يحدث داخل GenerateCertificateJob وقت التنفيذ الفعلي.
     *
     * @param  array<string, mixed>  $data  student_id, certificate_type, exam_id (اختياري)
     */
    public function execute(array $data, User $actor): Certificate
    {
        if (! $actor->hasRole('org-admin') && ! $actor->hasRole('super-admin')) {
            throw ValidationException::withMessages([
                'actor' => 'إصدار الشهادات مقصور على مدير المنظمة.',
            ]);
        }

        if (! $actor->can('certificates.generate')) {
            throw ValidationException::withMessages([
                'actor' => 'لا تملك صلاحية إصدار الشهادات.',
            ]);
        }

        if (! $this->featureEnabled($actor->organization_id)) {
            throw ValidationException::withMessages([
                'feature' => 'ميزة الشهادات غير مفعّلة لمنظمتك.',
            ]);
        }

        $student = StudentProfile::withoutGlobalScopes()->findOrFail($data['student_id']);

        if ($actor->organization_id === null || $actor->organization_id !== $student->organization_id) {
            throw ValidationException::withMessages([
                'student_id' => 'الطالب لا ينتمي لمنظمتك.',
            ]);
        }

        $certificate = Certificate::withoutGlobalScopes()->create([
            'organization_id' => $student->organization_id,
            'student_id' => $student->id,
            'exam_id' => $data['exam_id'] ?? null,
            'certificate_type' => $data['certificate_type'],
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
        return (bool) FeatureFlag::withoutGlobalScopes()
            ->where('organization_id', $organizationId)
            ->where('key', 'certificates.enabled')
            ->value('enabled');
    }
}
