<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\StudentTransfer;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * يتحقق من أن الطالب/المسجد/الحلقة الهدف موجودون في نفس منظمة الطالب
 * (منع النقل العابر للمنظمات) — انظر §2، §4 (Broken Access Control).
 */
class TransferStudentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('request', StudentTransfer::class);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $organizationId = $this->user()->organization_id;

        return [
            'student_id' => [
                'required',
                'integer',
                Rule::exists('student_profiles', 'id')->where('organization_id', $organizationId),
            ],
            'to_mosque_id' => [
                'required',
                'integer',
                Rule::exists('mosques', 'id')->where('organization_id', $organizationId),
            ],
            'to_circle_id' => [
                'nullable',
                'integer',
                Rule::exists('circles', 'id')->where('organization_id', $organizationId),
            ],
            'reason' => ['nullable', 'string', 'max:2000'],
            'metadata' => ['nullable', 'array'],
        ];
    }
}
