<?php

namespace App\Http\Requests;

use App\Enums\UserStatus;
use App\Models\TeacherProfile;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/**
 * التحقق من تعديل معلم. يبقى ربطه بحلقة واحدة على الأقل إلزامياً، وكل
 * الحلقات المختارة يجب أن تنتمي لمنظمة المسؤول الحالي.
 */
class UpdateTeacherRequest extends FormRequest
{
    public function authorize(): bool
    {
        $teacher = $this->route('teacher');

        return $teacher instanceof TeacherProfile
            && $this->user()->can('update', $teacher);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $teacher = $this->route('teacher');
        $userId = $teacher instanceof TeacherProfile ? $teacher->user_id : null;
        $organizationId = $this->user()->organization_id;

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($userId)],
            'password' => ['nullable', Password::defaults()],
            'phone' => ['nullable', 'string', 'max:30'],
            'specialization' => ['nullable', 'string', 'max:255'],
            'ijazah_details' => ['nullable', 'string', 'max:2000'],
            'hired_at' => ['nullable', 'date'],
            'status' => ['required', Rule::enum(UserStatus::class)],
            'circles' => ['required', 'array', 'min:1'],
            'circles.*' => [
                'integer',
                Rule::exists('circles', 'id')->where('organization_id', $organizationId),
            ],
        ];
    }
}
