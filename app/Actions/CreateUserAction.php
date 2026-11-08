<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

/**
 * إنشاء مستخدم إداري: organization_id يُسند من سياق المسؤول الحالي لا من
 * الطلب (منع Privilege Escalation)، والدور يُسند عبر Spatie بعد الحفظ.
 */
class CreateUserAction
{
    /**
     * @param  array<string, mixed>  $data  بيانات مصادَق عليها من StoreUserRequest::validated()
     */
    public function execute(array $data, User $admin): User
    {
        $user = User::create([
            'organization_id' => $admin->organization_id,
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make((string) $data['password']),
            'phone' => $data['phone'] ?? null,
            'status' => $data['status'],
        ]);

        $user->assignRole($data['role']);

        return $user;
    }
}
