<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\StudentProfile;
use App\Models\User;
use App\Support\RoleHierarchy;

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
            || $user->hasRole(RoleHierarchy::SUPER_ADMIN);
    }

    public function view(User $user, StudentProfile $student): bool
    {
        if (! $this->withinTenant($user, $student)) {
            return false;
        }

        // مدير النظام العام / مدير المنظمة / المشرف: وصول كامل لكل طلاب المنظومة.
        if ($user->hasRole([RoleHierarchy::SUPER_ADMIN, 'org-admin', 'supervisor'])) {
            return true;
        }

        // المعلم: فقط الطلاب المنتسبون إلى حلقاته المسندة إليه رسمياً.
        if ($user->hasRole('teacher')) {
            return $student->circle !== null
                && $student->circle->teacher_id === $user->id;
        }

        // الطالب: يرى ملفه الشخصي فقط.
        if ($user->hasRole('student')) {
            return $student->user_id === $user->id;
        }

        // ولي الأمر: يرى الأبناء المصرَّح لهم فقط.
        if ($user->hasRole('parent')) {
            return $this->guardianCanView($user, $student, 'view_profile');
        }

        return false;
    }

    public function create(User $user): bool
    {
        // Fail-Closed: بلا سياق منظمة لا يمكن نسبة الطالب لأي مستأجر.
        return $user->organization_id !== null
            && $user->can('students.manage');
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
        if ($user->hasRole(RoleHierarchy::SUPER_ADMIN)) {
            return true;
        }

        return $user->organization_id !== null
            && $user->organization_id === $student->organization_id;
    }
}
