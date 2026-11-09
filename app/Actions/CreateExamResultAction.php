<?php

declare(strict_types=1);

namespace App\Actions;

use App\DTOs\ExamResultData;
use App\Models\ExamResult;
use App\Models\User;

/**
 * تسجيل نتيجة طالب (draft/submitted) — المُصحِّح هو المستخدم الحالي
 * دائماً، لا يُقبل من الحمولة (منع انتحال هوية المصحح).
 */
class CreateExamResultAction
{
    public function execute(ExamResultData $data, User $examiner): ExamResult
    {
        return ExamResult::create([
            'exam_id' => $data->examId,
            'exam_section_id' => $data->examSectionId,
            'student_id' => $data->studentId,
            'score' => $data->score,
            'grade' => $data->grade,
            'examiner_id' => $examiner->id,
            'status' => $data->status,
        ]);
    }
}
