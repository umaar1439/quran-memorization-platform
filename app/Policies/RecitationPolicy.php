<?php

namespace App\Policies;

use App\Models\Recitation;
use App\Models\StudentProfile;
use App\Models\User;

/**
 * create: Teacher فقط + الطالب في حلقته + permission check.
 * view: مالك السجل أو Student صاحب التسميع أو Parent مصرح.
 * الدفاع الأساسي عن IDOR/Malicious Teacher Requests (§4، تهديد #1، #17).
 */
class RecitationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('recitations.view') || $user->hasRole('student') || $user->hasRole('parent');
    }

    public function view(User $user, Recitation $recitation): bool
    {
        if (! $this->withinTenant($user, $recitation)) {
            return false;
        }

        if ($user->hasRole('super-admin') || $user->hasRole('org-admin')) {
            return true;
        }

        if ($user->hasRole('supervisor')) {
            return $user->can('recitations.view');
        }

        if ($user->hasRole('teacher')) {
            return $recitation->teacher_id === $user->id;
        }

        if ($user->hasRole('student')) {
            return $recitation->student && $recitation->student->user_id === $user->id;
        }

        if ($user->hasRole('parent')) {
            $studentPolicy = new StudentProfilePolicy;

            return $recitation->student
                && $studentPolicy->guardianCanView($user, $recitation->student, 'view_recitations');
        }

        return false;
    }

    /**
     * Teacher فقط، والطالب المستهدف يجب أن يكون في حلقة يُدرّسها المعلم
     * فعلاً — يمنع تسجيل تسميع لطالب خارج حلقته (IDOR).
     */
    public function create(User $user, ?StudentProfile $student = null): bool
    {
        if (! $user->hasRole('teacher') || ! $user->can('recitations.create')) {
            return false;
        }

        if (! $student) {
            return true;
        }

        if ($user->organization_id === null || $user->organization_id !== $student->organization_id) {
            return false;
        }

        return $student->circle && $student->circle->teacher_id === $user->id;
    }

    public function update(User $user, Recitation $recitation): bool
    {
        if (! $this->withinTenant($user, $recitation)) {
            return false;
        }

        if (! $user->can('recitations.update')) {
            return false;
        }

        return $recitation->teacher_id === $user->id || $user->hasRole('org-admin') || $user->hasRole('super-admin');
    }

    private function withinTenant(User $user, Recitation $recitation): bool
    {
        if ($user->hasRole('super-admin')) {
            return true;
        }

        return $user->organization_id !== null
            && $user->organization_id === $recitation->organization_id;
    }
}
