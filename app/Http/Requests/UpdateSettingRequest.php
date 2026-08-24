<?php

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
            'key' => ['required', 'string', Rule::in(self::ALLOWED_KEYS)],
            'value' => ['required', 'string', 'max:1000'],
            'group' => ['nullable', 'string', 'max:100'],
        ];
    }
}
