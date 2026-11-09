<?php

declare(strict_types=1);

namespace App\Actions;

use App\DTOs\StudentGoalData;
use App\Enums\GoalStatus;
use App\Models\StudentGoal;
use App\Models\StudentProfile;

/**
 * إنشاء هدف طالب جديد: organization_id يُشتق من ملف الطالب (لا من الطلب)
 * لضمان اتساق المستأجر، ويبدأ الهدف دائماً بحالة active وبتحقق صفر.
 */
class CreateStudentGoalAction
{
    public function execute(StudentGoalData $data): StudentGoal
    {
        $student = StudentProfile::withoutGlobalScopes()->findOrFail($data->studentId);

        return StudentGoal::withoutGlobalScopes()->create([
            ...$data->toAttributes(),
            'organization_id' => $student->organization_id,
            'student_id' => $student->id,
            'achieved_amount' => 0,
            'completion_percentage' => 0,
            'status' => GoalStatus::Active,
        ]);
    }
}
