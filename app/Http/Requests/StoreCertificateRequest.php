<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\DTOs\CertificateData;
use App\Models\Certificate;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * يتحقق من صحة بيانات إصدار شهادة. authorize() يفوّض إلى
 * CertificatePolicy::generate (org-admin + certificates.generate +
 * feature flag مُفعّل — §7، §3).
 */
class StoreCertificateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('generate', Certificate::class);
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
            'exam_id' => [
                'nullable',
                'integer',
                Rule::exists('exams', 'id')->where('organization_id', $organizationId),
            ],
            'certificate_type' => ['required', 'string', 'max:100'],
        ];
    }

    /**
     * تحويل البيانات المُصادَق عليها إلى DTO لتمريرها إلى الـ Action.
     */
    public function toData(): CertificateData
    {
        return CertificateData::fromArray($this->validated());
    }
}
