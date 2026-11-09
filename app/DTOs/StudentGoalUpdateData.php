<?php

declare(strict_types=1);

namespace App\DTOs;

use App\Enums\GoalStatus;
use App\Enums\GoalTargetType;
use App\Enums\PeriodType;

/**
 * بيانات تعديل هدف طالب (PATCH جزئي) بعد المصادقة، تُبنى من
 * UpdateStudentGoalRequest::toData() وتُمرَّر إلى UpdateStudentGoalAction.
 *
 * PATCH يعني «عدّل ما أرسله العميل فقط»: لذلك نحتفظ بعلمَي `surahIdProvided`
 * و`juzNumberProvided` لأن تمرير null صريح (مسح النطاق) يختلف عن غياب الحقل
 * (إبقاء القيمة الحالية) — والتفريق عبر `array_key_exists` في fromArray().
 */
final readonly class StudentGoalUpdateData
{
    public function __construct(
        public ?PeriodType $periodType,
        public ?GoalTargetType $targetType,
        public ?int $surahId,
        public bool $surahIdProvided,
        public ?int $juzNumber,
        public bool $juzNumberProvided,
        public ?int $targetAmount,
        public ?int $achievedAmount,
        public ?string $periodStart,
        public ?string $periodEnd,
        public ?GoalStatus $status,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            periodType: isset($data['period_type'])
                ? ($data['period_type'] instanceof PeriodType
                    ? $data['period_type']
                    : PeriodType::from((string) $data['period_type']))
                : null,
            targetType: isset($data['target_type'])
                ? ($data['target_type'] instanceof GoalTargetType
                    ? $data['target_type']
                    : GoalTargetType::from((string) $data['target_type']))
                : null,
            surahId: isset($data['surah_id']) ? (int) $data['surah_id'] : null,
            surahIdProvided: array_key_exists('surah_id', $data),
            juzNumber: isset($data['juz_number']) ? (int) $data['juz_number'] : null,
            juzNumberProvided: array_key_exists('juz_number', $data),
            targetAmount: isset($data['target_amount']) ? (int) $data['target_amount'] : null,
            achievedAmount: isset($data['achieved_amount']) ? (int) $data['achieved_amount'] : null,
            periodStart: isset($data['period_start']) ? (string) $data['period_start'] : null,
            periodEnd: isset($data['period_end']) ? (string) $data['period_end'] : null,
            status: isset($data['status'])
                ? ($data['status'] instanceof GoalStatus
                    ? $data['status']
                    : GoalStatus::from((string) $data['status']))
                : null,
        );
    }

    /**
     * الحقول المُرسَلة فعلاً فقط — تُمرَّر إلى StudentGoal::update() كما هي.
     *
     * @return array<string, mixed>
     */
    public function attributes(): array
    {
        $attributes = array_filter([
            'period_type' => $this->periodType,
            'target_type' => $this->targetType,
            'target_amount' => $this->targetAmount,
            'achieved_amount' => $this->achievedAmount,
            'period_start' => $this->periodStart,
            'period_end' => $this->periodEnd,
            'status' => $this->status,
        ], static fn (mixed $value): bool => $value !== null);

        if ($this->surahIdProvided) {
            $attributes['surah_id'] = $this->surahId;
        }

        if ($this->juzNumberProvided) {
            $attributes['juz_number'] = $this->juzNumber;
        }

        return $attributes;
    }
}
