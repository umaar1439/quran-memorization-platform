<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use Spatie\Permission\Models\Role;

/**
 * إدارة صلاحيات الأدوار من الواجهة (شاشة /admin/roles).
 *
 * الحدود:
 * - roles.view لعرض المصفوفة، وroles.manage لتعديلها.
 * - الدور يجب أن يكون داخل منظمة المستخدم: الأدوار team-scoped، وفتح دور
 *   منظمة أخرى عبر تعديل id في الرابط = Cross-Tenant Access (IDOR).
 * - دور super-admin غير قابل للتعديل من الواجهة: هو طريق الإنقاذ إذا
 *   سُحبت صلاحية إدارة الأدوار من بقية الأدوار، ويُعاد ضبطه من الـ Seeder
 *   عند تجهيز المنظمة. تعديله من الواجهة قد يقفل النظام بالكامل.
 */
class RolePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('roles.view');
    }

    public function view(User $user, Role $role): bool
    {
        return $user->can('roles.view') && $this->withinTenant($user, $role);
    }

    public function update(User $user, Role $role): bool
    {
        if ($role->name === 'super-admin') {
            return false;
        }

        if (! $user->can('roles.manage')) {
            return false;
        }

        return $this->withinTenant($user, $role);
    }

    /**
     * الدور يخص منظمة المستخدم نفسها (لا يوجد دور عابر للمنظمات في هذا المشروع).
     */
    private function withinTenant(User $user, Role $role): bool
    {
        return $user->organization_id !== null
            && (int) $user->organization_id === (int) $role->organization_id;
    }
}
