<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\ExamResult;
use App\Models\User;

/**
 * تسجيل نتيجة طالب (draft/submitted) — المُصحِّح هو المستخدم الحالي
 * دائماً، لا يُقبل من الحمولة (منع انتحال هوية المصحح).
 */
class CreateExamResultAction
{
    /**
     * @param  array<string, mixed>  $data  بيانات مصادَق عليها من StoreExamResultRequest::validated()
     */
    public function execute(array $data, User $examiner): ExamResult
    {
        return ExamResult::create([
            'exam_id' => $data['exam_id'],
            'exam_section_id' => $data['exam_section_id'],
            'student_id' => $data['student_id'],
            'score' => $data['score'],
            'grade' => $data['grade'] ?? null,
            'examiner_id' => $examiner->id,
            'status' => $data['status'],
        ]);
    }
}
