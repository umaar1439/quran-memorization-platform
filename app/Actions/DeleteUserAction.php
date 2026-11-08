<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\User;

/**
 * حذف مستخدم إداري (Soft Delete) — التفويض يحدث في الـ Controller عبر
 * UserPolicy قبل الوصول إلى هذه الخطوة.
 */
class DeleteUserAction
{
    public function execute(User $user): void
    {
        $user->delete();
    }
}
