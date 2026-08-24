<?php

namespace App\Actions;

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
     * @param  array<string, mixed>  $data  بيانات مُصادَق عليها من StoreRecitationRequest::validated()
     */
    public function execute(array $data, User $teacher): Recitation
    {
        // شرط 1: المعلم مصادَق عليه.
        if (! $teacher->exists) {
            throw ValidationException::withMessages([
                'teacher' => 'المعلم غير مصادَق عليه.',
            ]);
        }

        // شرط 2: صلاحية recitations.create.
        if (! $teacher->can('recitations.create')) {
            throw ValidationException::withMessages([
                'teacher' => 'لا تملك صلاحية تسجيل التسميع.',
            ]);
        }

        // شرط 3+4: نفس المنظمة (المعلم والطالب).
        $student = StudentProfile::withoutGlobalScopes()->find($data['student_id']);

        if (! $student) {
            throw ValidationException::withMessages([
                'student_id' => 'الطالب غير موجود.',
            ]);
        }

        if ($teacher->organization_id === null || $teacher->organization_id !== $student->organization_id) {
            throw ValidationException::withMessages([
                'student_id' => 'الطالب لا ينتمي لمنظمتك.',
            ]);
        }

        // شرط 5: الطالب في حلقة يُدرّسها هذا المعلم فعلاً (IDOR Defense).
        if (! $student->circle || $student->circle->teacher_id !== $teacher->id) {
            throw ValidationException::withMessages([
                'student_id' => 'هذا الطالب ليس في حلقة تُدرّسها.',
            ]);
        }

        // شرط 8: idempotency_key موجود وصالح.
        if (empty($data['idempotency_key'])) {
            throw ValidationException::withMessages([
                'idempotency_key' => 'مفتاح idempotency مطلوب.',
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
                    'type' => $data['type'],
                    'surah_number' => $data['surah_number'],
                    'ayah_from' => $data['ayah_from'],
                    'ayah_to' => $data['ayah_to'],
                    'rating' => $data['rating'] ?? null,
                    'notes' => $data['notes'] ?? null,
                    'performed_at' => $data['performed_at'] ?? now(),
                    'idempotency_key' => $data['idempotency_key'],
                ]);
            } catch (QueryException $exception) {
                if (! $this->isUniqueConstraintViolation($exception)) {
                    throw $exception;
                }

                // مفتاح idempotency موجود مسبقاً — إعادة إرسال (Replay/Duplicate
                // Offline Sync، §4 تهديد #13، #14): نجاح صامت بإرجاع السجل الموجود.
                return Recitation::withoutGlobalScopes()
                    ->where('organization_id', $student->organization_id)
                    ->where('idempotency_key', $data['idempotency_key'])
                    ->firstOrFail();
            }

            foreach ($data['errors'] ?? [] as $error) {
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
            if ($data['type'] === RecitationType::New || $data['type'] === RecitationType::New->value) {
                $this->recommendExamOnCompletion->execute($student, $data['surah_number']);
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
