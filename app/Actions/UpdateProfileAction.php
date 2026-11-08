<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * تحديث الملف الشخصي للمستخدم الحالي. تغيير البريد يُبطل التحقق منه
 * (email_verified_at = null) لإجبار إعادة التحقق — منع انتحال البريد.
 */
class UpdateProfileAction
{
    /**
     * @param  array<string, mixed>  $data  بيانات مصادَق عليها من ProfileUpdateRequest::validated()
     */
    public function execute(User $user, array $data): User
    {
        return DB::transaction(function () use ($user, $data): User {
            $user->fill($data);

            if ($user->isDirty('email')) {
                $user->email_verified_at = null;
            }

            $user->save();

            return $user;
        });
    }
}
