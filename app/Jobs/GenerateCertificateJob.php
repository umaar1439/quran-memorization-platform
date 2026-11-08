<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Actions\CreateAuditLogAction;
use App\Enums\CertificateStatus;
use App\Models\Certificate;
use App\Models\StudentProfile;
use App\Models\User;
use App\Notifications\CertificateIssuedNotification;
use App\Support\FeatureFlagCache;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\PermissionRegistrar;

/**
 * يولّد ملف الشهادة في الخلفية (Queue Job) بدل دورة الطلب المتزامنة.
 * يعيد التحقق من الدور والصلاحية والـ feature flag وقت التنفيذ الفعلي
 * (دفاع طبقتين — §4 تهديد #2)، ثم يحفظ الملف على القرص الخاص ويربطه
 * بسجل التدقيق ويُرسل الإشعار.
 */
class GenerateCertificateJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * قفل تفرد لكل شهادة: يمنع توليد نفس الشهادة مرتين بالتوازي (كتابة
     * متعارضة على نفس file_path + إشعار مكرر للطالب). ينتهي تلقائياً بعد
     * أطول سلسلة محاولات حتى لا تبقى الشهادة معلّقة.
     */
    public int $uniqueFor = 900;

    public function uniqueId(): string
    {
        return 'certificate:'.$this->certificateId;
    }

    /**
     * توليد الشهادة يعتمد على القرص والتخزين؛ نسمح بثلاث محاولات متباعدة
     * قبل إعلان الفشل النهائي (والذي يُثبّت الحالة على failed).
     */
    public int $tries = 3;

    public int $timeout = 120;

    /**
     * @var list<int>
     */
    public array $backoff = [10, 60, 180];

    public function __construct(
        public readonly string $certificateId,
        public readonly int $actorId,
    ) {}

    public function handle(
        FeatureFlagCache $flags,
        PermissionRegistrar $permissions,
        CreateAuditLogAction $auditLog,
    ): void {
        $certificate = Certificate::withoutGlobalScopes()->find($this->certificateId);

        if (! $certificate) {
            return;
        }

        $actor = User::withoutGlobalScopes()->find($this->actorId);

        if (! $actor) {
            $this->markFailed($certificate);

            return;
        }

        // تعيين سياق المستأجر لفحوصات Spatie داخل الـ queue worker (لا middleware هنا).
        $permissions->setPermissionsTeamId($certificate->organization_id);

        // دفاع الطبقة الثانية: إعادة التحقق من الدور والصلاحية والـ feature flag وقت التنفيذ.
        if (! $actor->hasRole('org-admin') && ! $actor->hasRole('super-admin')) {
            $this->markFailed($certificate);

            return;
        }

        if (! $actor->can('certificates.generate')) {
            $this->markFailed($certificate);

            return;
        }

        if (! $flags->enabled($certificate->organization_id, 'certificates.enabled')) {
            $this->markFailed($certificate);

            return;
        }

        $student = StudentProfile::withoutGlobalScopes()->find($certificate->student_id);

        if (! $student) {
            $this->markFailed($certificate);

            return;
        }

        $filePath = "certificates/{$certificate->organization_id}/{$certificate->verification_code}.pdf";

        Storage::disk('local')->put($filePath, $this->renderSimplePdf($student, $certificate->verification_code));

        $certificate->update([
            'status' => CertificateStatus::Issued,
            'file_path' => $filePath,
        ]);

        $auditLog->execute(
            organizationId: $certificate->organization_id,
            event: 'certificate.issued',
            auditableType: Certificate::class,
            auditableId: $certificate->id,
            oldValues: null,
            newValues: [
                'student_id' => $certificate->student_id,
                'certificate_type' => $certificate->certificate_type,
                'verification_code' => $certificate->verification_code,
            ],
            user: $actor,
        );

        $student->user?->notify(new CertificateIssuedNotification(
            certificateId: $certificate->id,
            certificateType: $certificate->certificate_type,
            verificationCode: $certificate->verification_code,
        ));
    }

    private function markFailed(Certificate $certificate): void
    {
        $certificate->update(['status' => CertificateStatus::Failed]);
    }

    /**
     * يُستدعى بعد استنفاد كل المحاولات — يمنع بقاء الشهادة معلّقة ويُسجّل السياق.
     */
    public function failed(?\Throwable $exception): void
    {
        Log::error('Certificate generation job failed permanently.', [
            'certificate_id' => $this->certificateId,
            'actor_id' => $this->actorId,
            'error' => $exception?->getMessage(),
        ]);

        Certificate::withoutGlobalScopes()
            ->whereKey($this->certificateId)
            ->update(['status' => CertificateStatus::Failed]);
    }

    private function renderSimplePdf(StudentProfile $student, string $verificationCode): string
    {
        return "%PDF-1.4\n% Certificate for student #{$student->id}\n% Verification: {$verificationCode}\n";
    }
}
