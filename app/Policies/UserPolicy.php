<?php

namespace App\Policies;

use App\Models\User;

/**
 * إدارة المستخدمين (users.*) — محصورة بحد المستأجر (organization_id match)
 * إلا لـ super-admin. لا يجوز لمستخدم حذف/تعديل نفسه عبر هذه الصلاحية
 * (يُستخدم ProfileController لذلك) — انظر نمط MosquePolicy/CirclePolicy.
 */
class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('users.view') || $user->hasRole('super-admin');
    }

    public function view(User $user, User $target): bool
    {
        if (! $this->withinTenant($user, $target)) {
            return false;
        }

        return $user->can('users.view');
    }

    public function create(User $user): bool
    {
        return $user->can('users.create');
    }

    public function update(User $user, User $target): bool
    {
        if (! $this->withinTenant($user, $target)) {
            return false;
        }

        return $user->can('users.update');
    }

    public function delete(User $user, User $target): bool
    {
        if ($user->is($target)) {
            return false;
        }

        if (! $this->withinTenant($user, $target)) {
            return false;
        }

        return $user->can('users.delete');
    }

    private function withinTenant(User $user, User $target): bool
    {
        if ($user->hasRole('super-admin')) {
            return true;
        }

        return $user->organization_id !== null
            && $user->organization_id === $target->organization_id;
    }
}
