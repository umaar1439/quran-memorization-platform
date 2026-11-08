<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Setting;
use App\Models\User;

/**
 * manage — org-admin فقط (ضمن منظمته) أو super-admin.
 * القراءة العامة غير حساسة ولا تحتاج صلاحية (§7).
 */
class SettingPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Setting $setting): bool
    {
        return $this->withinTenant($user, $setting);
    }

    public function manage(User $user, ?Setting $setting = null): bool
    {
        if (! $user->hasRole('org-admin') && ! $user->hasRole('super-admin')) {
            return false;
        }

        if (! $user->can('settings.manage')) {
            return false;
        }

        if ($setting) {
            return $this->withinTenant($user, $setting);
        }

        return true;
    }

    public function update(User $user, Setting $setting): bool
    {
        return $this->manage($user, $setting);
    }

    public function delete(User $user, Setting $setting): bool
    {
        return $this->manage($user, $setting);
    }

    private function withinTenant(User $user, Setting $setting): bool
    {
        if ($user->hasRole('super-admin')) {
            return true;
        }

        return $user->organization_id !== null
            && $user->organization_id === $setting->organization_id;
    }
}
