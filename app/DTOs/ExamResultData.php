<?php

declare(strict_types=1);

namespace App\DTOs;

use App\Enums\ExamResultStatus;

/**
 * بيانات تسجيل نتيجة طالب (draft/submitted) بعد المصادقة، تُبنى من
 * StoreExamResultRequest::toData() وتُمرَّر إلى CreateExamResultAction.
 *
 * لا تحمل `examiner_id`: المصحح هو المستخدم المصادَق عليه داخل الـ Action
 * (منع انتحال هوية المصحح)، ولا `approved_by/approved_at` لأن الاعتماد
 * يمر عبر ApproveExamResultAction فقط.
 */
final readonly class ExamResultData
{
    public function __construct(
        public int $examId,
        public int $examSectionId,
        public int $studentId,
        public int $score,
        public ?string $grade,
        public ExamResultStatus $status,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            examId: (int) $data['exam_id'],
            examSectionId: (int) $data['exam_section_id'],
            studentId: (int) $data['student_id'],
            score: (int) $data['score'],
            grade: isset($data['grade']) ? (string) $data['grade'] : null,
            status: $data['status'] instanceof ExamResultStatus
                ? $data['status']
                : ExamResultStatus::from((string) $data['status']),
        );
    }
}
