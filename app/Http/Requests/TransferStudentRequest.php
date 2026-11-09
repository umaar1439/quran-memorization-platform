<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\DTOs\EnrollmentData;
use App\Enums\GenericStatus;
use App\Models\StudentTransfer;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * يتحقق من أن الطالب/المسجد/الحلقة الهدف موجودون في نفس منظمة الطالب
 * (منع النقل العابر للمنظمات) — انظر §2، §4 (Broken Access Control).
 *
 * Face validity: الهدف يجب أن يكون غير محذوف ونشطاً — `Rule::exists` يتجاهل
 * Soft Deletes افتراضياً، ولا معنى لنقل طالب إلى مسجد/حلقة معطّلة.
 * التحقق النهائي من تطابق الحلقة مع المسجد يجري داخل TransferStudentAction
 * بعد قفل صف الطالب (Race Conditions).
 */
class TransferStudentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->organization_id !== null
            && $this->user()->can('request', StudentTransfer::class);
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
                Rule::exists('student_profiles', 'id')
                    ->where('organization_id', $organizationId)
                    ->whereNull('deleted_at'),
            ],
            'to_mosque_id' => [
                'required',
                'integer',
                Rule::exists('mosques', 'id')
                    ->where('organization_id', $organizationId)
                    ->where('status', GenericStatus::Active->value)
                    ->whereNull('deleted_at'),
            ],
            'to_circle_id' => [
                'nullable',
                'integer',
                Rule::exists('circles', 'id')
                    ->where('organization_id', $organizationId)
                    ->where('status', GenericStatus::Active->value)
                    ->whereNull('deleted_at'),
            ],
            'reason' => ['nullable', 'string', 'max:2000'],
            'metadata' => ['nullable', 'array'],
            'metadata.*' => ['nullable', 'scalar'],
        ];
    }

    /**
     * تحويل البيانات المُصادَق عليها إلى DTO لتمريرها إلى الـ Action.
     */
    public function toData(): EnrollmentData
    {
        return EnrollmentData::fromArray($this->validated());
    }
}
