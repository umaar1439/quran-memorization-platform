<?php

declare(strict_types=1);

namespace App\DTOs;

/**
 * بيانات مُصادَق عليها لتغيير انتساب طالب (نقل بين مسجد/حلقة) — تُبنى من
 * TransferStudentRequest::validated() وتُمرَّر إلى TransferStudentAction
 * بديلاً عن المصفوفة الحرة.
 *
 * `organization_id` غير موجود: يُشتق من منظمة الطالب داخل الـ Action بعد
 * قفل صفه، فلا يُقبل من العميل أي نطاق مستأجر.
 */
final readonly class EnrollmentData
{
    /**
     * @param  array<string, mixed>|null  $metadata
     */
    public function __construct(
        public int $studentId,
        public int $toMosqueId,
        public ?int $toCircleId,
        public ?string $reason,
        public ?array $metadata,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $metadata = $data['metadata'] ?? null;

        return new self(
            studentId: (int) $data['student_id'],
            toMosqueId: (int) $data['to_mosque_id'],
            toCircleId: isset($data['to_circle_id']) && $data['to_circle_id'] !== ''
                ? (int) $data['to_circle_id']
                : null,
            reason: self::blankToNull($data['reason'] ?? null),
            metadata: is_array($metadata) && $metadata !== [] ? $metadata : null,
        );
    }

    private static function blankToNull(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
