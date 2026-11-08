<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\DTOs\StudentData;
use App\Enums\StudentStatus;
use App\Models\StudentProfile;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/**
 * التحقق من إنشاء طالب وربطه بحلقة تنتمي حصراً لمنظمة المسؤول الحالي.
 * mosque_id يُشتق من الحلقة المختارة في Controller (لا يُقبل من العميل).
 */
class StoreStudentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', StudentProfile::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $organizationId = $this->user()->organization_id;

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'password' => ['required', Password::defaults()],
            'circle_id' => [
                'required',
                'integer',
                Rule::exists('circles', 'id')->where('organization_id', $organizationId),
            ],
            'enrollment_number' => [
                'required',
                'string',
                'max:50',
                Rule::unique('student_profiles', 'enrollment_number'),
            ],
            'birth_date' => ['nullable', 'date'],
            'current_juz' => ['nullable', 'integer', 'min:1', 'max:30'],
            'status' => ['required', Rule::enum(StudentStatus::class)],
        ];
    }

    /**
     * تحويل البيانات المُصادَق عليها إلى DTO لتمريرها إلى الـ Action.
     */
    public function toDto(): StudentData
    {
        return StudentData::fromArray($this->validated());
    }
}
