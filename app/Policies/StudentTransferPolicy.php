<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\StudentTransfer;
use App\Models\User;

/**
 * request — supervisor+، approve — org-admin+ (§3 مصفوفة الصلاحيات).
 */
class StudentTransferPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('students.transfer') || $user->hasRole('org-admin') || $user->hasRole('super-admin');
    }

    public function view(User $user, StudentTransfer $transfer): bool
    {
        return $this->withinTenant($user, $transfer)
            && ($user->can('students.transfer') || $user->hasRole('org-admin') || $user->hasRole('super-admin'));
    }

    public function request(User $user): bool
    {
        return $user->hasRole('supervisor') || $user->hasRole('org-admin') || $user->hasRole('super-admin');
    }

    public function approve(User $user, StudentTransfer $transfer): bool
    {
        if (! $this->withinTenant($user, $transfer)) {
            return false;
        }

        return $user->hasRole('org-admin') || $user->hasRole('super-admin');
    }

    private function withinTenant(User $user, StudentTransfer $transfer): bool
    {
        if ($user->hasRole('super-admin')) {
            return true;
        }

        return $user->organization_id !== null
            && $user->organization_id === $transfer->organization_id;
    }
}
