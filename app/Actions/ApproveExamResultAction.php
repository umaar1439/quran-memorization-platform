<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\ExamResultStatus;
use App\Models\ExamResult;
use App\Models\User;
use App\Notifications\ExamResultNotification;

/**
 * اعتماد نتيجة امتحان (قفل نهائي): status=approved + approved_by/at،
 * ثم إشعار الطالب. إعادة الاعتماد لا تُغيّر شيئاً (Idempotent).
 */
class ApproveExamResultAction
{
    public function execute(ExamResult $result, User $approver): ExamResult
    {
        if ($result->status === ExamResultStatus::Approved) {
            return $result;
        }

        $result->update([
            'status' => ExamResultStatus::Approved,
            'approved_by' => $approver->id,
            'approved_at' => now(),
        ]);

        $result->student?->user?->notify(new ExamResultNotification(
            studentId: $result->student_id,
            examResultId: $result->id,
            grade: (string) ($result->grade ?? $result->score),
        ));

        return $result;
    }
}
