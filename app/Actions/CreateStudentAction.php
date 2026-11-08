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
 */
class CreateStudentAction
{
    public function execute(StudentData $data, User $admin): StudentProfile
    {
        return DB::transaction(function () use ($data, $admin): StudentProfile {
            $organizationId = (int) $admin->organization_id;
            $circle = Circle::query()->findOrFail($data->circleId);

            $user = new User;
            $user->name = $data->name;
            $user->email = $data->email;
            $user->password = (string) $data->password;
            $user->organization_id = $organizationId;
            $user->save();

            $user->assignRole('student');

            return StudentProfile::query()->create([
                'user_id' => $user->id,
                'organization_id' => $organizationId,
                'mosque_id' => $circle->mosque_id,
                'circle_id' => $circle->id,
                'enrollment_number' => $data->enrollmentNumber,
                'birth_date' => $data->birthDate,
                'current_juz' => $data->currentJuz,
                'status' => $data->status,
            ]);
        });
    }
}
