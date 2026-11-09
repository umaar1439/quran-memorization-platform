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
 * التحقق من تعديل مستخدم إداري. منفصل عن StoreUserRequest لأن كلمة المرور
 * اختيارية هنا: تركها فارغة يعني "لا تغيير" (لا مسح للقيمة الحالية).
 *
 * التفويض على الهدف (نفسه/مدير النظام العام/رتبة أعلى/منظمة أخرى) يتم في
 * UserPolicy قبل الوصول إلى القواعد.
 */
class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->route('user');

        return $user instanceof User && $this->user()->can('update', $user);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var User|null $target */
        $target = $this->route('user');

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($target?->id),
            ],
            'password' => ['nullable', Password::defaults()],
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
