<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\FeatureFlag;
use Illuminate\Foundation\Http\FormRequest;

/**
 * التحقق من تبديل علم ميزة — القواعد كانت داخل الـ Controller سابقاً.
 */
class UpdateFeatureFlagRequest extends FormRequest
{
    public function authorize(): bool
    {
        $flag = $this->route('flag');

        return $flag instanceof FeatureFlag
            && $this->user()->can('update', $flag);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'enabled' => ['required', 'boolean'],
        ];
    }

    /**
     * الحمولة المُصادَق عليها: قيمة منطقية واحدة لتبديل العلم.
     *
     * لا نُغلِّفها في DTO عمداً — DTO يوصف بنية بيانات (عدة حقول/أنواع)،
     * وقيمة boolean مفردة لا تضيف أي بنية تُحمى من الأخطاء. اسم `toData`
     * موحَّد مع بقية الطلبات، وأي حقل جديد مستقبلاً يتحول إلى DTO.
     */
    public function toData(): bool
    {
        return $this->boolean('enabled');
    }
}
