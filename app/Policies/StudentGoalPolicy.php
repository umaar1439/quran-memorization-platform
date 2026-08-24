<?php

namespace App\Policies;

use App\Enums\GoalStatus;
use App\Models\StudentGoal;
use App\Models\StudentProfile;
use App\Models\User;

/**
 * Org Admin/Supervisor يديران أهداف طلاب منظمتهم، Teacher يدير أهداف
 * طلاب حلقته فقط، Student/Parent يرون فقط (§2، §3). التعديل مسموح فقط
 * قبل الاكتمال (status != completed) — §5 دورة حياة البيانات.
 */
class StudentGoalPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('students.view')
            || $user->hasRole('teacher')
            || $user->hasRole('student')
            || $user->hasRole('parent');
    }

    public function view(User $user, StudentGoal $goal): bool
    {
        if (! $this->withinTenant($user, $goal)) {
            return false;
        }

        if ($user->hasRole('super-admin') || $user->hasRole('org-admin') || $user->hasRole('supervisor')) {
            return $user->can('students.view');
        }

        if ($user->hasRole('teacher')) {
            return $goal->student && $goal->student->circle && $goal->student->circle->teacher_id === $user->id;
        }

        if ($user->hasRole('student')) {
            return $goal->student && $goal->student->user_id === $user->id;
        }

        if ($user->hasRole('parent')) {
            $studentPolicy = new StudentProfilePolicy;

            return $goal->student
                && $studentPolicy->guardianCanView($user, $goal->student);
        }

        return false;
    }

    public function create(User $user, ?StudentProfile $student = null): bool
    {
        if (! $user->can('students.manage') && ! $user->hasRole('teacher')) {
            return false;
        }

        if (! $student) {
            return $user->can('students.manage') || $user->hasRole('teacher');
        }

        if ($user->organization_id === null || $user->organization_id !== $student->organization_id) {
            return false;
        }

        if ($user->hasRole('super-admin') || $user->hasRole('org-admin') || $user->hasRole('supervisor')) {
            return true;
        }

        if ($user->hasRole('teacher')) {
            return $student->circle && $student->circle->teacher_id === $user->id;
        }

        return false;
    }

    public function update(User $user, StudentGoal $goal): bool
    {
        if (! $this->withinTenant($user, $goal)) {
            return false;
        }

        if ($goal->status !== GoalStatus::Active) {
            return false;
        }

        if ($user->hasRole('super-admin') || $user->hasRole('org-admin') || $user->hasRole('supervisor')) {
            return $user->can('students.manage');
        }

        if ($user->hasRole('teacher')) {
            return $goal->student && $goal->student->circle && $goal->student->circle->teacher_id === $user->id;
        }

        return false;
    }

    private function withinTenant(User $user, StudentGoal $goal): bool
    {
        if ($user->hasRole('super-admin')) {
            return true;
        }

        return $user->organization_id !== null
            && $user->organization_id === $goal->organization_id;
    }
}
