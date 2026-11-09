<?php

declare(strict_types=1);

namespace App\DTOs;

/**
 * إعداد واحد (مفتاح/قيمة/مجموعة) بعد المصادقة، تُبنى من
 * UpdateSettingRequest::toData() وتُمرَّر إلى UpdateSettingsAction.
 *
 * المفاتيح المسموحة تُفحص في FormRequest (Rule::in) قبل الوصول إلى هنا،
 * فلا يحتاج الـ Action إلى قائمة سماح خاصة به.
 */
final readonly class SettingEntryData
{
    public function __construct(
        public string $key,
        public ?string $value,
        public ?string $group,
    ) {}

    /**
     * @param  array<string, mixed>  $item
     */
    public static function fromArray(array $item): self
    {
        return new self(
            key: (string) $item['key'],
            value: isset($item['value']) ? (string) $item['value'] : null,
            group: isset($item['group']) ? (string) $item['group'] : null,
        );
    }
}
