<?php

declare(strict_types=1);

namespace App\DTOs;

use App\Enums\ExamStatus;

/**
 * بيانات تعديل امتحان (PATCH جزئي) بعد المصادقة، تُبنى من
 * UpdateExamRequest::toData() وتُمرَّر إلى UpdateExamAction.
 *
 * PATCH يعني "عدّل ما أرسله العميل فقط": لذلك نحتفظ بعلم `scheduledAtProvided`
 * لأن `scheduled_at=null` صريح (مسح التاريخ) يختلف عن غياب الحقل (إبقاء
 * التاريخ الحالي) — والتفريق بينهما عبر `array_key_exists` في fromArray().
 */
final readonly class ExamUpdateData
{
    public function __construct(
        public ?string $title,
        public ?string $examType,
        public ?string $scheduledAt,
        public bool $scheduledAtProvided,
        public ?ExamStatus $status,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $scheduledAtProvided = array_key_exists('scheduled_at', $data);

        return new self(
            title: isset($data['title']) ? (string) $data['title'] : null,
            examType: isset($data['exam_type']) ? (string) $data['exam_type'] : null,
            scheduledAt: $scheduledAtProvided && $data['scheduled_at'] !== null
                ? (string) $data['scheduled_at']
                : null,
            scheduledAtProvided: $scheduledAtProvided,
            status: isset($data['status'])
                ? ($data['status'] instanceof ExamStatus ? $data['status'] : ExamStatus::from((string) $data['status']))
                : null,
        );
    }

    /**
     * الحقول المُرسَلة فعلاً فقط — تُمرَّر إلى Exam::update() كما هي.
     *
     * @return array<string, mixed>
     */
    public function attributes(): array
    {
        $attributes = array_filter([
            'title' => $this->title,
            'exam_type' => $this->examType,
            'status' => $this->status,
        ], static fn (mixed $value): bool => $value !== null);

        if ($this->scheduledAtProvided) {
            $attributes['scheduled_at'] = $this->scheduledAt;
        }

        return $attributes;
    }
}
