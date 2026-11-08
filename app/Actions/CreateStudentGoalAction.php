<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\GoalStatus;
use App\Models\StudentGoal;
use App\Models\StudentProfile;

/**
 * إنشاء هدف طالب جديد: organization_id يُشتق من ملف الطالب (لا من الطلب)
 * لضمان اتساق المستأجر، ويبدأ الهدف دائماً بحالة active وبتحقق صفر.
 */
class CreateStudentGoalAction
{
    /**
     * @param  array<string, mixed>  $data  بيانات مصادَق عليها من StoreStudentGoalRequest::validated()
     */
    public function execute(array $data): StudentGoal
    {
        $student = StudentProfile::withoutGlobalScopes()->findOrFail($data['student_id']);

        return StudentGoal::withoutGlobalScopes()->create([
            'organization_id' => $student->organization_id,
            'student_id' => $student->id,
            'period_type' => $data['period_type'],
            'target_type' => $data['target_type'],
            'surah_id' => $data['surah_id'] ?? null,
            'juz_number' => $data['juz_number'] ?? null,
            'target_amount' => $data['target_amount'],
            'achieved_amount' => 0,
            'completion_percentage' => 0,
            'period_start' => $data['period_start'],
            'period_end' => $data['period_end'],
            'status' => GoalStatus::Active,
        ]);
    }
}
