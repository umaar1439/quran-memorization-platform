<?php

declare(strict_types=1);

namespace App\DTOs;

use App\Enums\GenericStatus;

/**
 * بيانات مُصادَق عليها لإنشاء/تحديث حلقة، تُبنى من
 * StoreCircleRequest::validated() وتُمرَّر إلى الـ Actions بديلاً عن
 * المصفوفات الحرة.
 *
 * mosque_id/teacher_id سبق التحقق من انتمائهما لمنظمة الفاعل ومن صلاحية
 * teacher_id ليكون مسؤولاً عن حلقة (StoreCircleRequest) — الـ DTO ناقل
 * بيانات فقط ولا يعيد التحقق.
 */
final readonly class CircleData
{
    public function __construct(
        public int $mosqueId,
        public ?int $teacherId,
        public string $name,
        public ?string $level,
        public ?int $capacity,
        public GenericStatus $status,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            mosqueId: (int) $data['mosque_id'],
            teacherId: self::nullableInt($data['teacher_id'] ?? null),
            name: trim((string) $data['name']),
            level: self::nullableString($data['level'] ?? null),
            capacity: self::nullableInt($data['capacity'] ?? null),
            status: GenericStatus::from((string) $data['status']),
        );
    }

    private static function nullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim((string) $value);

        return $trimmed === '' ? null : $trimmed;
    }

    /**
     * النموذج يُرسل "" للحقول الرقمية الاختيارية؛ تُطبَّع إلى null بدل 0.
     */
    private static function nullableInt(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (int) $value;
    }
}
