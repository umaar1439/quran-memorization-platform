<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use App\Support\RoleHierarchy;

/**
 * إدارة المستخدمين (users.*) — محصورة بحد المستأجر (organization_id match)
 * إلا لـ super-admin.
 *
 * حدود إضافية تمنع الاستيلاء على الحسابات (Account Takeover):
 * - لا يجوز تعديل/حذف النفس عبر هذه الصلاحية (الملف الشخصي مساره
 *   ProfileController) — يُمنع رفع الصلاحيات بسطر واحد.
 * - لا يجوز لغير super-admin المساس بحساب super-admin.
 * - لا يجوز إدارة مستخدم رتبته أعلى من رتبة الفاعل أو مساوية لها
 *   (RoleHierarchy) — يمنع إعادة تعيين كلمة مرور نظير.
 *
 * Validation vs Authorization: هنا 403 للأهداف غير المسموح بها، أما الدور
 * المُسنَد (اختيار أعلى من رتبة الفاعل) فيُرفض في الـ Actions كخطأ تحقق
 * على الحقل role حتى تظهر رسالة مفهومة في النموذج.
 */
class UserPolicy
{
    public function __construct(
        private readonly RoleHierarchy $hierarchy,
    ) {}

    public function viewAny(User $user): bool
    {
        return $user->can('users.view') || $user->hasRole(RoleHierarchy::SUPER_ADMIN);
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
        if ($user->is($target)) {
            return false;
        }

        if (! $this->withinTenant($user, $target)) {
            return false;
        }

        if ($this->isProtectedSuperAdmin($user, $target)) {
            return false;
        }

        return $user->can('users.update') && $this->hierarchy->canManage($user, $target);
    }

    public function delete(User $user, User $target): bool
    {
        if ($user->is($target)) {
            return false;
        }

        if (! $this->withinTenant($user, $target)) {
            return false;
        }

        if ($this->isProtectedSuperAdmin($user, $target)) {
            return false;
        }

        return $user->can('users.delete') && $this->hierarchy->canManage($user, $target);
    }

    private function withinTenant(User $user, User $target): bool
    {
        if ($user->hasRole(RoleHierarchy::SUPER_ADMIN)) {
            return true;
        }

        return $user->organization_id !== null
            && $user->organization_id === $target->organization_id;
    }

    /**
     * حساب super-admin محمي من أي فاعل ليس super-admin (حتى لو شاركه المنظمة).
     */
    private function isProtectedSuperAdmin(User $user, User $target): bool
    {
        return $target->hasRole(RoleHierarchy::SUPER_ADMIN)
            && ! $user->hasRole(RoleHierarchy::SUPER_ADMIN);
    }
}
