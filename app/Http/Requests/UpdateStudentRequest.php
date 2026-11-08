<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\DTOs\StudentData;
use App\Enums\StudentStatus;
use App\Models\StudentProfile;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * التحقق من تعديل طالب ونقله بين الحلقات. الحلقة الجديدة يجب أن تنتمي
 * حصراً لمنظمة المسؤول الحالي.
 */
class UpdateStudentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $student = $this->route('student');

        return $student instanceof StudentProfile
            && $this->user()->can('update', $student);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $student = $this->route('student');
        $organizationId = $this->user()->organization_id;

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($student instanceof StudentProfile ? $student->user_id : null),
            ],
            'circle_id' => [
                'required',
                'integer',
                Rule::exists('circles', 'id')->where('organization_id', $organizationId),
            ],
            'enrollment_number' => [
                'required',
                'string',
                'max:50',
                Rule::unique('student_profiles', 'enrollment_number')->ignore($student instanceof StudentProfile ? $student->id : null),
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
