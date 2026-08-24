<?php

namespace App\Policies;

use App\Models\Circle;
use App\Models\User;

class CirclePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('circles.view') || $user->hasRole('super-admin');
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

    private function withinTenant(User $user, Circle $circle): bool
    {
        if ($user->hasRole('super-admin')) {
            return true;
        }

        return $user->organization_id !== null
            && $user->organization_id === $circle->organization_id;
    }
}
