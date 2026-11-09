<?php

declare(strict_types=1);

namespace App\Actions;

use App\DTOs\StudentData;
use App\Models\Circle;
use App\Models\StudentProfile;
use Illuminate\Support\Facades\DB;

/**
 * تحديث طالب: حساب المستخدم + الملف الدراسي + الحلقة، داخل معاملة واحدة.
 * الحقول المحمية (organization_id/user_id) لا تُعدَّل من هنا إطلاقاً.
 *
 * كلمة المرور تُحدَّث فقط عند تمرير قيمة غير فارغة (الـ DTO يطبّع `''` إلى
 * null)، فلا يمسّ حفظُ الاسم كلمةَ مرور الطالب.
 */
class UpdateStudentAction
{
    public function execute(StudentProfile $student, StudentData $data): StudentProfile
    {
        $organizationId = $student->organization_id;

        if ($organizationId === null) {
            abort(403, __('messages.students.no_organization_context'));
        }

        return DB::transaction(function () use ($student, $data, $organizationId): StudentProfile {
            $circle = $this->resolveCircle($data->circleId, $organizationId);

            $user = $student->user;
            // ملف بلا حساب مستخدم = بيانات تالفة؛ 404 صريح بدل تحديث صامت ناقص.
            abort_if($user === null, 404, __('messages.students.missing_account'));

            $user->name = $data->name;
            $user->email = $data->email;

            if ($data->password !== null) {
                $user->password = $data->password;
            }

            $user->save();

            $student->mosque_id = $circle->mosque_id;
            $student->circle_id = $circle->id;
            $student->enrollment_number = $data->enrollmentNumber;
            $student->birth_date = $data->birthDate;
            $student->current_juz = $data->currentJuz;
            $student->status = $data->status;
            $student->save();

            return $student;
        });
    }

    /**
     * نفس حراسة CreateStudentAction: النطاق صريح لا يعتمد على Global Scope
     * (يتخطاه super-admin) ولا على التفويض في الـ Controller وحده.
     */
    private function resolveCircle(int $circleId, int $organizationId): Circle
    {
        return Circle::query()
            ->where('organization_id', $organizationId)
            ->whereKey($circleId)
            ->firstOrFail();
    }
}
