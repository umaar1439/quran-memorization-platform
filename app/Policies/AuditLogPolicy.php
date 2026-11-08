<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\AuditLog;
use App\Models\User;

/**
 * viewAny — org-admin+ (قراءة فقط، لا تعديل أبداً). الجدول Append-Only
 * على مستوى التطبيق ولا Policy تسمح بـ update/delete إطلاقاً (§1، §5).
 */
class AuditLogPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('org-admin') || $user->hasRole('super-admin');
    }

    public function view(User $user, AuditLog $log): bool
    {
        if (! $this->withinTenant($user, $log)) {
            return false;
        }

        return $user->hasRole('org-admin') || $user->hasRole('super-admin');
    }

    private function withinTenant(User $user, AuditLog $log): bool
    {
        if ($user->hasRole('super-admin')) {
            return true;
        }

        return $user->organization_id !== null
            && $user->organization_id === $log->organization_id;
    }
}
