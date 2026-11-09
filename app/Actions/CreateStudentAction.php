<?php

declare(strict_types=1);

namespace App\Actions;

use App\DTOs\StudentData;
use App\Models\Circle;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * إنشاء طالب: حساب User بدور student + StudentProfile مربوط بحلقة،
 * داخل معاملة واحدة. `mosque_id` يُشتق من الحلقة، و`organization_id`
 * يُسند صراحةً لأنه محمي من التعبئة الجماعية في User.
 *
 * الإسناد صريح حقلاً بحقل بدل `create($array)` (نفس نمط CreateMosqueAction)
 * حتى لا يعتمد السلوك على $fillable وحده.
 */
class CreateStudentAction
{
    public function execute(StudentData $data, User $actor): StudentProfile
    {
        $organizationId = $actor->organization_id;

        // Fail-Closed: كان `(int) null` يُنتج 0 فيُنشأ طالب في منظمة وهمية بدل
        // رفض واضح. لا منظمة ⇒ لا طالب.
        if ($organizationId === null) {
            abort(403, __('messages.students.no_organization_context'));
        }

        return DB::transaction(function () use ($data, $organizationId): StudentProfile {
            $circle = $this->resolveCircle($data->circleId, $organizationId);

            $user = new User;
            $user->name = $data->name;
            $user->email = $data->email;
            // كلمة المرور تُمرَّر نصاً: الـ cast `hashed` على User يجزّئها.
            $user->password = (string) $data->password;
            $user->organization_id = $organizationId;
            $user->save();

            $user->assignRole('student');

            $student = new StudentProfile;
            $student->user_id = $user->id;
            $student->organization_id = $organizationId;
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
     * الحلقة تُجلب بمنظمة صريحة: Global Scope لا يُطبَّق على super-admin،
     * والـ Action لا يعتمد على سياق الطلب وحده (Fail-Closed).
     */
    private function resolveCircle(int $circleId, int $organizationId): Circle
    {
        return Circle::query()
            ->where('organization_id', $organizationId)
            ->whereKey($circleId)
            ->firstOrFail();
    }
}
