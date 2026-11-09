<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\DTOs\UserData;
use App\Enums\UserStatus;
use App\Models\User;
use App\Queries\UserDirectoryQuery;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/**
 * التحقق من إنشاء مستخدم إداري. organization_id لا يُقبل من الطلب إطلاقاً
 * (يُشتق من سياق المسؤول داخل CreateUserAction)، والدور يُقيَّد بالأدوار
 * القابلة للإسناد في UserDirectoryQuery::ROLES — مصدر واحد بدل تكرار القائمة.
 *
 * التعديل له Request مستقل (UpdateUserRequest) لأن قاعدة كلمة المرور تختلف.
 */
class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', User::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'password' => ['required', Password::defaults()],
            'phone' => ['nullable', 'string', 'max:30'],
            'role' => ['required', 'string', Rule::in(UserDirectoryQuery::ROLES)],
            'status' => ['required', Rule::enum(UserStatus::class)],
        ];
    }

    public function toData(): UserData
    {
        /** @var array<string, mixed> $validated */
        $validated = $this->validated();

        return UserData::fromArray($validated);
    }
}
