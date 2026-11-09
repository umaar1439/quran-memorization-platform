<?php

declare(strict_types=1);

namespace App\DTOs;

/**
 * بيانات نموذج إنشاء امتحان (Submission) بعد المصادقة، تُبنى من
 * StoreExamRequest::toData() وتُمرَّر إلى CreateExamAction بديلاً عن المصفوفة.
 *
 * لا تحمل `status` ولا `created_by`: الحالة تبدأ draft دوماً والمنشئ هو
 * المستخدم المصادَق عليه داخل الـ Action — لا يُقبلان من العميل إطلاقاً.
 */
final readonly class ExamSubmissionData
{
    /**
     * @param  list<ExamSectionData>  $sections
     */
    public function __construct(
        public ?int $mosqueId,
        public ?int $circleId,
        public string $title,
        public string $examType,
        public ?string $scheduledAt,
        public array $sections = [],
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            mosqueId: isset($data['mosque_id']) ? (int) $data['mosque_id'] : null,
            circleId: isset($data['circle_id']) ? (int) $data['circle_id'] : null,
            title: (string) $data['title'],
            examType: (string) $data['exam_type'],
            scheduledAt: isset($data['scheduled_at']) ? (string) $data['scheduled_at'] : null,
            sections: array_values(array_map(
                static fn (mixed $section): ExamSectionData => $section instanceof ExamSectionData
                    ? $section
                    : ExamSectionData::fromArray((array) $section),
                (array) ($data['sections'] ?? []),
            )),
        );
    }
}
