<?php

namespace App\Policies;

use App\Models\StudentProfile;
use App\Models\User;

/**
 * Org Admin يرى الكل في منظمته، Supervisor يرى مساجده، Teacher يرى
 * طلاب حلقته فقط، Student يرى نفسه فقط، Parent يرى أبناءه المصرح بهم
 * فقط (عبر guardian_student) — انظر §2، §3.
 */
class StudentProfilePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('students.view')
            || $user->hasRole('student')
            || $user->hasRole('parent')
            || $user->hasRole('super-admin');
    }

    public function view(User $user, StudentProfile $student): bool
    {
        if (! $this->withinTenant($user, $student)) {
            return false;
        }

        if ($user->hasRole('super-admin') || $user->hasRole('org-admin')) {
            return true;
        }

        if ($user->hasRole('supervisor')) {
            return $user->can('students.view');
        }

        if ($user->hasRole('teacher')) {
            return $student->circle && $student->circle->teacher_id === $user->id;
        }

        if ($user->hasRole('student')) {
            return $student->user_id === $user->id;
        }

        if ($user->hasRole('parent')) {
            return $this->guardianCanView($user, $student, 'view_profile');
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->can('students.manage');
    }

    public function update(User $user, StudentProfile $student): bool
    {
        if (! $this->withinTenant($user, $student)) {
            return false;
        }

        return $user->can('students.manage');
    }

    public function delete(User $user, StudentProfile $student): bool
    {
        if (! $this->withinTenant($user, $student)) {
            return false;
        }

        return $user->can('students.manage');
    }

    /**
     * تحقق من صلاحية ولي الأمر المحددة عبر guardian_student JSONB.
     * مفتاح الصلاحية اختياري: إن لم يُطلب مفتاح محدد، يكفي وجود الربط.
     */
    public function guardianCanView(User $user, StudentProfile $student, ?string $permissionKey = null): bool
    {
        $guardian = $user->guardian;

        if (! $guardian) {
            return false;
        }

        $pivot = $guardian->students()
            ->wherePivot('student_id', $student->id)
            ->first()
            ?->pivot;

        if (! $pivot) {
            return false;
        }

        if ($permissionKey === null) {
            return true;
        }

        $permissions = $pivot->permissions;

        if (is_string($permissions)) {
            $permissions = json_decode($permissions, true) ?? [];
        }

        return (bool) ($permissions[$permissionKey] ?? false);
    }

    private function withinTenant(User $user, StudentProfile $student): bool
    {
        if ($user->hasRole('super-admin')) {
            return true;
        }

        return $user->organization_id !== null
            && $user->organization_id === $student->organization_id;
    }
}
