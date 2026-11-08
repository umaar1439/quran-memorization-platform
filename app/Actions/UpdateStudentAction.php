<?php

declare(strict_types=1);

namespace App\Actions;

use App\DTOs\StudentData;
use App\Models\Circle;
use App\Models\StudentProfile;
use Illuminate\Support\Facades\DB;

/**
 * تحديث طالب: حساب المستخدم + الملف الدراسي + الحلقة، داخل معاملة واحدة.
 */
class UpdateStudentAction
{
    public function execute(StudentProfile $student, StudentData $data): StudentProfile
    {
        return DB::transaction(function () use ($student, $data): StudentProfile {
            $circle = Circle::query()->findOrFail($data->circleId);

            $user = $student->user;

            if ($user !== null) {
                $user->name = $data->name;
                $user->email = $data->email;
                $user->save();
            }

            $student->update([
                'mosque_id' => $circle->mosque_id,
                'circle_id' => $circle->id,
                'enrollment_number' => $data->enrollmentNumber,
                'birth_date' => $data->birthDate,
                'current_juz' => $data->currentJuz,
                'status' => $data->status,
            ]);

            return $student;
        });
    }
}
