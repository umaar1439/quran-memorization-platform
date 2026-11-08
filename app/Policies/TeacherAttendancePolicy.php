<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\TeacherAttendance;
use App\Models\User;

/**
 * Teacher يرى حضوره فقط، Supervisor/Admin يرى الكل في نطاقه.
 */
class TeacherAttendancePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('attendance.view') || $user->hasRole('teacher');
    }

    public function view(User $user, TeacherAttendance $attendance): bool
    {
        if (! $this->withinTenant($user, $attendance)) {
            return false;
        }

        if ($user->hasRole('super-admin') || $user->hasRole('org-admin') || $user->hasRole('supervisor')) {
            return $user->can('attendance.view');
        }

        if ($user->hasRole('teacher')) {
            return $attendance->teacher_id === $user->id;
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->hasRole('supervisor') || $user->hasRole('org-admin') || $user->hasRole('super-admin');
    }

    public function update(User $user, TeacherAttendance $attendance): bool
    {
        if (! $this->withinTenant($user, $attendance)) {
            return false;
        }

        return $user->hasRole('supervisor') || $user->hasRole('org-admin') || $user->hasRole('super-admin');
    }

    private function withinTenant(User $user, TeacherAttendance $attendance): bool
    {
        if ($user->hasRole('super-admin')) {
            return true;
        }

        return $user->organization_id !== null
            && $user->organization_id === $attendance->organization_id;
    }
}
