<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Actions\CreateAuditLogAction;
use App\Models\Circle;
use App\Models\FeatureFlag;
use App\Models\Mosque;
use App\Models\Recitation;
use App\Models\Setting;
use App\Models\StudentAttendance;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/**
 * يستمع لأحداث Eloquent (created/updated/deleted) على Models المطلوب
 * تدقيقها تلقائياً (Phase 14 نقطة 3): تغيير صلاحيات/إنشاء أو تعديل مستخدم
 * (User)، إنشاء تسميع (Recitation)، تغيير إعدادات (Setting)، تغيير
 * feature flags (FeatureFlag)، أي تغيير في المساجد والحلقات
 * (Mosque/Circle) بما في ذلك الحذف المتسلسل للحلقات عند حذف مسجد، ودورة
 * حياة ملف الطالب (StudentProfile) بما في ذلك نقله بين الحلقات عبر الإدارة.
 * يحذف الحقول الحساسة (password، remember_token) من old/new values قبل
 * التخزين — CreateAuditLogAction يقوم بذلك تلقائياً عبر آلية redact الخاصة به.
 *
 * ملاحظة: نقل الطالب (student.transferred) وإصدار الشهادات وتصحيح النتائج
 * تُسجَّل بالفعل يدوياً داخل Actions المخصصة لها (TransferStudentAction،
 * GenerateCertificateAction، CorrectExamResultAction) لتفادي ازدواج
 * السجلات — TransferStudentAction يحفظ الملف بـ saveQuietly لهذا السبب —
 * وهذا Listener يُكمِّل التغطية على العمليات غير المُدقَّقة بعد.
 *
 * حضور الطلاب: التسجيل الفردي (RecordAttendanceAction) يُدقَّق تلقائياً هنا،
 * أما حفظ الورقة اليومية (BulkRecordAttendanceAction) فيستخدم upsert —
 * لا يُطلق أحداث Eloquent — ويُسجِّل تدقيقاً واحداً مُجمَّعاً بنفسه. إضافة
 * StudentAttendance إلى الخريطة لا تُضاعف سجل الحفظ الجماعي لأن upsert
 * لا يمر بأحداث النموذج.
 */
class AuditModelChanges
{
    /**
     * الحقول الحساسة التي لا تُسجَّل أبداً في audit_logs حتى قبل التمرير
     * لـ CreateAuditLogAction (طبقة حماية إضافية).
     */
    private const SENSITIVE_FIELDS = ['password', 'remember_token'];

    /**
     * Models المُدقَّقة ونبذة اسم الحدث لكل منها (user.created …).
     * عام لأن EventServiceProvider يسجّل المستمع من مفاتيح هذه الخريطة —
     * مصدر واحد يمنع نسيان التسجيل عند إضافة Model جديد.
     *
     * @var array<class-string, string>
     */
    public const AUDITED_EVENTS = [
        User::class => 'user',
        Setting::class => 'setting',
        FeatureFlag::class => 'feature_flag',
        Recitation::class => 'recitation',
        Mosque::class => 'mosque',
        Circle::class => 'circle',
        StudentProfile::class => 'student',
        StudentAttendance::class => 'student_attendance',
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
