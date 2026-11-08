<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * تحديث مستخدم إداري: الحقول الأساسية + كلمة المرور إن أُرسلت + مزامنة
 * الدور. الحقول المحمية (organization_id) لا تُعدَّل هنا إطلاقاً.
 */
class UpdateUserAction
{
    /**
     * @param  array<string, mixed>  $data  بيانات مصادَق عليها من StoreUserRequest::validated()
     */
    public function execute(User $user, array $data): User
    {
        return DB::transaction(function () use ($user, $data): User {
            $user->fill([
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'status' => $data['status'],
            ]);

            if (! empty($data['password'])) {
                $user->password = Hash::make((string) $data['password']);
            }

            $user->save();
            $user->syncRoles([$data['role']]);

            return $user;
        });
    }
}
