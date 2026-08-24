<?php

namespace App\Listeners;

use App\Actions\CreateAuditLogAction;
use App\Models\FeatureFlag;
use App\Models\Recitation;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/**
 * يستمع لأحداث Eloquent (created/updated/deleted) على Models المطلوب
 * تدقيقها تلقائياً (Phase 14 نقطة 3): تغيير صلاحيات/إنشاء أو تعديل مستخدم
 * (User)، إنشاء تسميع (Recitation)، تغيير إعدادات (Setting)، تغيير
 * feature flags (FeatureFlag). يحذف الحقول الحساسة (password،
 * remember_token) من old/new values قبل التخزين — CreateAuditLogAction
 * يقوم بذلك تلقائياً عبر آلية redact الخاصة به.
 *
 * ملاحظة: نقل الطالب (student.transferred) وإصدار الشهادات وتصحيح النتائج
 * تُسجَّل بالفعل يدوياً داخل Actions المخصصة لها (TransferStudentAction،
 * GenerateCertificateAction، CorrectExamResultAction) لتفادي ازدواج
 * السجلات — هذا Listener يُكمِّل التغطية على العمليات غير المُدقَّقة بعد.
 */
class AuditModelChanges
{
    /**
     * الحقول الحساسة التي لا تُسجَّل أبداً في audit_logs حتى قبل التمرير
     * لـ CreateAuditLogAction (طبقة حماية إضافية).
     */
    private const SENSITIVE_FIELDS = ['password', 'remember_token'];

    /**
     * @var array<class-string, string>
     */
    private const AUDITED_EVENTS = [
        User::class => 'user',
        Setting::class => 'setting',
        FeatureFlag::class => 'feature_flag',
        Recitation::class => 'recitation',
    ];

    public function __construct(
        private readonly CreateAuditLogAction $createAuditLog,
    ) {}

    public function handleCreated(Model $model): void
    {
        $this->record($model, 'created');
    }

    public function handleUpdated(Model $model): void
    {
        $this->record($model, 'updated');
    }

    public function handleDeleted(Model $model): void
    {
        $this->record($model, 'deleted');
    }

    private function record(Model $model, string $action): void
    {
        $label = self::AUDITED_EVENTS[get_class($model)] ?? null;

        if ($label === null) {
            return;
        }

        $organizationId = $this->resolveOrganizationId($model);

        if ($organizationId === null) {
            return;
        }

        $oldValues = $action === 'created' ? null : $this->cleanse($model->getOriginal());
        $newValues = $action === 'deleted' ? null : $this->cleanse($model->getAttributes());

        $this->createAuditLog->execute(
            organizationId: $organizationId,
            event: "{$label}.{$action}",
            auditableType: get_class($model),
            auditableId: $model->getKey(),
            oldValues: $oldValues,
            newValues: $newValues,
            user: Auth::user(),
            request: request(),
        );
    }

    private function resolveOrganizationId(Model $model): ?int
    {
        return $model->organization_id ?? null;
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    private function cleanse(array $attributes): array
    {
        foreach (self::SENSITIVE_FIELDS as $field) {
            unset($attributes[$field]);
        }

        return $attributes;
    }
}
