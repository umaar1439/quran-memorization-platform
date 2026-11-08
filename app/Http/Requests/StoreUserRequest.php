<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/**
 * التحقق من إنشاء/تعديل مستخدم من قبل الإدارة. organization_id و role
 * لا يُعبَّآن جماعياً على Model — يُضبطان صريحاً في Controller بعد
 * التحقق من صلاحية المسؤول الحالي (انظر UserPolicy).
 */
class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->route('user');

        return $user
            ? $this->user()->can('update', $user)
            : $this->user()->can('create', User::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $userId = $this->route('user')?->id;

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($userId),
            ],
            'password' => [$userId ? 'nullable' : 'required', Password::defaults()],
            'phone' => ['nullable', 'string', 'max:30'],
            'role' => ['required', 'string', Rule::in(['org-admin', 'supervisor', 'teacher', 'student', 'parent'])],
            'status' => ['required', Rule::enum(UserStatus::class)],
        ];
    }
}
