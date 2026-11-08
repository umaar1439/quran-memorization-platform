<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Report;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * يتحقق من صحة بيانات طلب تقرير جديد. authorize() يفوّض إلى
 * ReportPolicy::generate.
 */
class StoreReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('generate', Report::class);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'report_type' => ['required', 'string', 'max:100'],
            'parameters' => ['nullable', 'array'],
        ];
    }
}
