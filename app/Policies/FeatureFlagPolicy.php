<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\FeatureFlag;
use App\Models\User;

/**
 * manage — org-admin (self-service flags فقط) أو super-admin.
 * الأعلام الجوهرية للنظام (organization_id = null) حصراً لـ super-admin (§3، §6.4).
 */
class FeatureFlagPolicy
{
    /**
     * أعلام self-service يُسمح لـ Org Admin بتفعيلها/تعطيلها لمنظمته.
     */
    public const SELF_SERVICE_FLAGS = [
        'certificates.enabled',
        'advanced-reports.enabled',
    ];

    public function viewAny(User $user): bool
    {
        return $user->can('features.manage');
    }

    public function view(User $user, FeatureFlag $flag): bool
    {
        return $this->manage($user, $flag);
    }

    public function manage(User $user, ?FeatureFlag $flag = null): bool
    {
        if ($user->hasRole('super-admin')) {
            return $user->can('features.manage');
        }

        if (! $user->hasRole('org-admin') || ! $user->can('features.manage')) {
            return false;
        }

        if (! $flag) {
            return true;
        }

        if (! in_array($flag->key, self::SELF_SERVICE_FLAGS, true)) {
            return false;
        }

        return $user->organization_id !== null
            && $user->organization_id === $flag->organization_id;
    }

    public function update(User $user, FeatureFlag $flag): bool
    {
        return $this->manage($user, $flag);
    }
}
