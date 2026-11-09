<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Circle;
use App\Models\User;
use App\Support\RoleHierarchy;

/**
 * كل فحص يبدأ بحد المستأجر (organization_id match) قبل صلاحية Spatie.
 * المعلم يرى حلقاته فقط؛ ومن لا يملك circles.view لا يرى شيئاً.
 */
class CirclePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('circles.view') || $user->hasRole(RoleHierarchy::SUPER_ADMIN);
    }

    public function view(User $user, Circle $circle): bool
    {
        if (! $this->withinTenant($user, $circle)) {
            return false;
        }

        if ($user->hasRole('teacher')) {
            return $circle->teacher_id === $user->id;
        }

        return $user->can('circles.view');
    }

    public function create(User $user): bool
    {
        return $user->can('circles.manage');
    }

    public function update(User $user, Circle $circle): bool
    {
        if (! $this->withinTenant($user, $circle)) {
            return false;
        }

        return $user->can('circles.manage');
    }

    public function delete(User $user, Circle $circle): bool
    {
        if (! $this->withinTenant($user, $circle)) {
            return false;
        }

        return $user->can('circles.manage');
    }

    /**
     * حد المستأجر: Super Admin يتجاوزه بالتصميم؛ غير ذلك تطابق تام.
     * مقارنة تنتهي بـ null تُرجِع false دائماً (Fail-Closed).
     */
    private function withinTenant(User $user, Circle $circle): bool
    {
        if ($user->hasRole(RoleHierarchy::SUPER_ADMIN)) {
            return true;
        }

        return $user->organization_id !== null
            && $user->organization_id === $circle->organization_id;
    }
}
