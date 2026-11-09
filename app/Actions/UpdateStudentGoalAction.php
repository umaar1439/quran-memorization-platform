<?php

declare(strict_types=1);

namespace App\Actions;

use App\DTOs\StudentGoalUpdateData;
use App\Enums\GoalStatus;
use App\Models\StudentGoal;
use App\Notifications\GoalCompletionNotification;

/**
 * تحديث هدف طالب، مع إشعار إكمال يُرسل فقط عند الانتقال الفعلي إلى
 * status=completed (لا عند كل تحديث) — Phase 13 نقطة 3.
 */
class UpdateStudentGoalAction
{
    public function execute(StudentGoal $goal, StudentGoalUpdateData $data): StudentGoal
    {
        $wasCompleted = $goal->status === GoalStatus::Completed;

        $attributes = $data->attributes();

        if ($attributes !== []) {
            $goal->update($attributes);
        }

        if (! $wasCompleted && $goal->status === GoalStatus::Completed) {
            $goal->student?->user?->notify(new GoalCompletionNotification(
                studentId: $goal->student_id,
                goalId: $goal->id,
                description: __('messages.goals.completed'),
            ));
        }

        return $goal;
    }
}
