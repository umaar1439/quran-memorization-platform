<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\User;

/**
 * حذف مستخدم إداري (Soft Delete) — كتابة واحدة، لذا لا معاملة ولا تدقيق
 * يدوي: AuditModelChanges يسجّل user.deleted تلقائياً.
 *
 * التفويض (users.delete + نفس المنظمة + منع حذف النفس + حماية super-admin
 * وذوي الرتبة الأعلى/المساوية) يحدث في UserPolicy قبل الوصول إلى هنا.
 */
class DeleteUserAction
{
    public function execute(User $user): void
    {
        $user->delete();
    }
}
