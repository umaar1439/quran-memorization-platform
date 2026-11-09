<?php

declare(strict_types=1);

namespace App\DTOs;

use App\Enums\GenericStatus;

/**
 * بيانات مُصادَق عليها لإنشاء/تحديث مسجد، تُبنى من
 * StoreMosqueRequest::validated() وتُمرَّر إلى الـ Actions بديلاً عن
 * المصفوفات الحرة (Loose Arrays).
 *
 * organization_id غير موجود هنا بالتصميم: يُشتق من الفاعل داخل الـ Action
 * ولا يُقبل من العميل إطلاقاً (منع Cross-Tenant Creation).
 */
final readonly class MosqueData
{
    public function __construct(
        public string $name,
        public ?string $address,
        public ?string $city,
        public ?string $phone,
        public GenericStatus $status,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            name: trim((string) $data['name']),
            address: self::nullableString($data['address'] ?? null),
            city: self::nullableString($data['city'] ?? null),
            phone: self::nullableString($data['phone'] ?? null),
            status: GenericStatus::from((string) $data['status']),
        );
    }

    /**
     * النص الفارغ/المسافات يُطبَّع إلى null: الفرق بين "لم يُرسَل" و"فارغ"
     * يظهر في قاعدة البيانات كفراغ حقيقي لا كنص " ".
     */
    private static function nullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim((string) $value);

        return $trimmed === '' ? null : $trimmed;
    }
}
