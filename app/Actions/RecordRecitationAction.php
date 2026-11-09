<?php

declare(strict_types=1);

namespace App\Actions;

use App\DTOs\RecitationData;
use App\Enums\RecitationType;
use App\Models\Recitation;
use App\Models\RecitationError;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * ينفّذ تسجيل تسميع بعد التحقق من الشروط الثمانية (§15 من وثيقة
 * التحقق المعماري — Race Conditions / Replay Attacks / IDOR):
 *   1. المعلم مصادَق عليه
 *   2. لديه صلاحية recitations.create
 *   3. ينتمي لنفس المنظمة
 *   4. الطالب في نفس المنظمة
 *   5. الطالب في حلقة يُدرّسها هذا المعلم
 *   6. النطاق القرآني صالح (يُفرض عبر StoreRecitationRequest + Rules)
 *   7. Form Request validation مررت (مسؤولية الطبقة المستدعية)
 *   8. idempotency_key موجود وصالح
 *
 * الشروط 1-5 و 8 تُفرض هنا مباشرة (دفاع طبقة ثانية Defense-in-Depth
 * فوق Policy/FormRequest، لا اعتماداً حصرياً عليهما).
 */
class RecordRecitationAction
{
    public function __construct(
        private readonly RecordTeacherActivityAction $recordTeacherActivity,
        private readonly RecommendExamOnCompletionAction $recommendExamOnCompletion,
    ) {}

    /**
     * @param  RecitationData  $data  بيانات مُصادَق عليها ومكتوبة الأنواع من StoreRecitationRequest::toData()
     */
    public function execute(RecitationData $data, User $teacher): Recitation
    {
        // شرط 1: المعلم مصادَق عليه.
        if (! $teacher->exists) {
            throw ValidationException::withMessages([
                'teacher' => __('messages.recitations.unauthenticated'),
            ]);
        }

        // شرط 2: صلاحية recitations.create.
        if (! $teacher->can('recitations.create')) {
            throw ValidationException::withMessages([
                'teacher' => __('messages.recitations.forbidden'),
            ]);
        }

        // شرط 3+4: نفس المنظمة (المعلم والطالب).
        $student = StudentProfile::withoutGlobalScopes()->find($data->studentId);

        if (! $student) {
            throw ValidationException::withMessages([
                'student_id' => __('messages.recitations.student_missing'),
            ]);
        }

        if ($teacher->organization_id === null || $teacher->organization_id !== $student->organization_id) {
            throw ValidationException::withMessages([
                'student_id' => __('messages.certificates.student_other_org'),
            ]);
        }

        // شرط 5: الطالب في حلقة يُدرّسها هذا المعلم فعلاً (IDOR Defense).
        if (! $student->circle || $student->circle->teacher_id !== $teacher->id) {
            throw ValidationException::withMessages([
                'student_id' => __('messages.recitations.not_your_circle'),
            ]);
        }

        // شرط 8: idempotency_key موجود وصالح.
        if ($data->idempotencyKey === '') {
            throw ValidationException::withMessages([
                'idempotency_key' => __('messages.recitations.idempotency_required'),
            ]);
        }

        return DB::transaction(function () use ($data, $teacher, $student) {
            try {
                $recitation = Recitation::withoutGlobalScopes()->create([
                    'organization_id' => $student->organization_id,
                    'student_id' => $student->id,
                    'teacher_id' => $teacher->id,
                    'mosque_id' => $student->mosque_id,
                    'circle_id' => $student->circle_id,
                    'type' => $data->type,
                    'surah_number' => $data->surahNumber,
                    'ayah_from' => $data->ayahFrom,
                    'ayah_to' => $data->ayahTo,
                    'rating' => $data->rating,
                    'notes' => $data->notes,
                    'performed_at' => $data->performedAt ?? now(),
                    'idempotency_key' => $data->idempotencyKey,
                ]);
            } catch (QueryException $exception) {
                if (! $this->isUniqueConstraintViolation($exception)) {
                    throw $exception;
                }

                // مفتاح idempotency موجود مسبقاً — إعادة إرسال (Replay/Duplicate
                // Offline Sync، §4 تهديد #13، #14): نجاح صامت بإرجاع السجل الموجود.
                return Recitation::withoutGlobalScopes()
                    ->where('organization_id', $student->organization_id)
                    ->where('idempotency_key', $data->idempotencyKey)
                    ->firstOrFail();
            }

            foreach ($data->errors as $error) {
                RecitationError::withoutGlobalScopes()->create([
                    'recitation_id' => $recitation->id,
                    'organization_id' => $student->organization_id,
                    'error_type' => $error['error_type'],
                    'count' => $error['count'] ?? 1,
                    'notes' => $error['notes'] ?? null,
                ]);
            }

            $this->recordTeacherActivity->execute(
                $student->organization_id,
                $teacher->id,
                $student->mosque_id,
                $recitation->performed_at,
            );

            // Auto-trigger توصية بامتحان عند اكتمال سورة (Phase 9 §3) — لا إنشاء تلقائي.
            if ($data->type === RecitationType::New) {
                $this->recommendExamOnCompletion->execute($student, $data->surahNumber);
            }

            return $recitation;
        });
    }

    /**
     * يتحقق أن الاستثناء ناتج عن مخالفة Unique Constraint (idempotency_key)
     * لا خطأ قاعدة بيانات آخر — يدعم SQLite (اختبارات) وPostgreSQL (إنتاج).
     */
    private function isUniqueConstraintViolation(QueryException $exception): bool
    {
        $sqlState = $exception->errorInfo[0] ?? null;

        // PostgreSQL: 23505 unique_violation. SQLite: 23000 constraint violation.
        return in_array($sqlState, ['23505', '23000'], true);
    }
}
