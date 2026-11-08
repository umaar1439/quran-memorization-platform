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

    public function enabled(): bool
    {
        return $this->boolean('enabled');
    }
}
