<?php

declare(strict_types=1);

namespace App\DTOs;

use App\Enums\GoalTargetType;
use App\Enums\PeriodType;

/**
 * بيانات إنشاء هدف طالب بعد المصادقة، تُبنى من
 * StoreStudentGoalRequest::toData() وتُمرَّر إلى CreateStudentGoalAction.
 *
 * `organization_id` و`achieved_amount` و`status` غير موجودة عمداً: الأولى
 * تُشتق من ملف الطالب (اتساق المستأجر)، والبقية حالة بداية يفرضها الخادم.
 */
final readonly class StudentGoalData
{
    public function __construct(
        public int $studentId,
        public PeriodType $periodType,
        public GoalTargetType $targetType,
        public ?int $surahId,
        public ?int $juzNumber,
        public int $targetAmount,
        public string $periodStart,
        public string $periodEnd,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            studentId: (int) $data['student_id'],
            periodType: $data['period_type'] instanceof PeriodType
                ? $data['period_type']
                : PeriodType::from((string) $data['period_type']),
            targetType: $data['target_type'] instanceof GoalTargetType
                ? $data['target_type']
                : GoalTargetType::from((string) $data['target_type']),
            surahId: isset($data['surah_id']) ? (int) $data['surah_id'] : null,
            juzNumber: isset($data['juz_number']) ? (int) $data['juz_number'] : null,
            targetAmount: (int) $data['target_amount'],
            periodStart: (string) $data['period_start'],
            periodEnd: (string) $data['period_end'],
        );
    }

    /**
     * الحقول القابلة للتعبئة في StudentGoal::create() — القيم المحمية
     * (organization_id/status/achieved_amount) يضيفها الـ Action.
     *
     * @return array<string, mixed>
     */
    public function toAttributes(): array
    {
        return [
            'student_id' => $this->studentId,
            'period_type' => $this->periodType,
            'target_type' => $this->targetType,
            'surah_id' => $this->surahId,
            'juz_number' => $this->juzNumber,
            'target_amount' => $this->targetAmount,
            'period_start' => $this->periodStart,
            'period_end' => $this->periodEnd,
        ];
    }
}
