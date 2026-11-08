<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Setting;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * يتحقق من أن key ضمن القائمة المسموحة و value مناسب لنوعها.
 * authorize() يفوّض لـ SettingPolicy::manage (org-admin فقط).
 */
class UpdateSettingRequest extends FormRequest
{
    /**
     * قائمة مفاتيح الإعدادات المسموح بها. مصدر واحد للحقيقة يمنع
     * إدخال مفاتيح تعسفية غير مصمَّمة (§4 Mass Assignment).
     */
    public const ALLOWED_KEYS = [
        'circle_label',
        'mosque_label',
        'exam_pass_threshold',
        'date_format',
        'default_locale',
    ];

    public function authorize(): bool
    {
        return $this->user()->can('manage', Setting::class);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'settings' => ['required', 'array', 'min:1'],
            'settings.*.key' => ['required', 'string', 'max:120', Rule::in(self::ALLOWED_KEYS)],
            'settings.*.value' => ['nullable', 'string', 'max:2000'],
            'settings.*.group' => ['nullable', 'string', 'max:80'],
        ];
    }

    /**
     * الإعدادات المُصادَق عليها بصيغة DTO خفيفة (مصفوفة مفاتيح مسموحة صراحةً).
     *
     * @return list<array{key: string, value: string|null, group: string|null}>
     */
    public function settings(): array
    {
        /** @var array<int, array{key: string, value?: string|null, group?: string|null}> $items */
        $items = $this->validated('settings');

        return array_values(array_map(static fn (array $item): array => [
            'key' => $item['key'],
            'value' => $item['value'] ?? null,
            'group' => $item['group'] ?? null,
        ], $items));
    }
}
