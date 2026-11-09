<?php

declare(strict_types=1);

namespace App\DTOs;

/**
 * بيانات تصحيح نتيجة معتمدة (Correction Workflow) بعد المصادقة، تُبنى من
 * CorrectExamResultRequest::toData() وتُمرَّر إلى CorrectExamResultAction.
 *
 * `reason` إلزامي: التصحيح لا يحدث دون سبب مُدقَّق في audit_log.
 */
final readonly class ExamCorrectionData
{
    public function __construct(
        public int $score,
        public ?string $grade,
        public string $reason,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            score: (int) $data['score'],
            grade: isset($data['grade']) ? (string) $data['grade'] : null,
            reason: (string) $data['reason'],
        );
    }
}
