<?php

namespace App\Http\Requests;

use App\Enums\UserStatus;
use App\Models\TeacherProfile;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/**
 * التحقق من إنشاء معلم. إلزامي ربطه بحلقة واحدة على الأقل، وكل الحلقات
 * يجب أن تنتمي حصراً لمنظمة المسؤول الحالي (منع تسرب عابر للمنظمات).
 */
class StoreTeacherRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', TeacherProfile::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $organizationId = $this->user()->organization_id;

        return [
            'user_id' => [
                'nullable',
                'integer',
                Rule::exists('users', 'id')->where('organization_id', $organizationId),
            ],
            'name' => ['required_without:user_id', 'string', 'max:255'],
            'email' => [
                'required_without:user_id',
                'email',
                'max:255',
                Rule::unique('users', 'email'),
            ],
            'password' => ['required_without:user_id', Password::defaults()],
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
