<?php

declare(strict_types=1);

namespace App\Queries;

use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * مصفوفة الأدوار والصلاحيات لشاشة /admin/roles: الأدوار داخل منظمة الفاعل
 * فقط (الأدوار team-scoped)، بترتيب عرض ثابت، مع صلاحيات كل دور.
 *
 * سبب وجودها: إبقاء RoleController نحيفاً (نفس نمط UserDirectoryQuery)،
 * ووضع منطق "أي دور قابل للتعديل" في مكان واحد يعتمد RolePolicy.
 */
final class RoleMatrixQuery
{
    /**
     * @return list<array{id: int, name: string, editable: bool, permissions: list<string>}>
     */
    public function forActor(User $actor): array
    {
        $roles = Role::query()
            ->where('guard_name', 'web')
            ->where('organization_id', $actor->organization_id)
            ->with('permissions:id,name')
            ->get()
            ->keyBy('name');

        return collect($this->roleOrder())
            ->map(static fn (string $name): ?Role => $roles->get($name))
            ->filter()
            ->map(fn (Role $role): array => [
                'id' => $role->id,
                'name' => $role->name,
                'editable' => $actor->can('update', $role),
                'permissions' => $role->permissions->pluck('name')->values()->all(),
            ])
            ->values()
            ->all();
    }

    /**
     * قائمة الصلاحيات عالمية ومحدودة الحجم — بلا ترقيم.
     *
     * @return list<string>
     */
    public function permissionNames(): array
    {
        return Permission::query()
            ->where('guard_name', 'web')
            ->orderBy('name')
            ->pluck('name')
            ->all();
    }

    /**
     * ترتيب عرض ثابت يبدأ بـ super-admin ثم الأدوار القابلة للإسناد.
     *
     * @return list<string>
     */
    private function roleOrder(): array
    {
        return ['super-admin', ...UserDirectoryQuery::ROLES];
    }
}
