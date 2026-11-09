<?php

declare(strict_types=1);

namespace App\Actions;

use App\DTOs\RolePermissionData;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

/**
 * مزامنة صلاحيات دور داخل منظمة المستخدم + تسجيل التغيير في audit_logs.
 *
 * قواعد إلزامية (غير قابلة للتجاوز من الواجهة):
 * - دور super-admin محمي من التعديل (طريق الإنقاذ عند الخطأ).
 * - منع رفع الصلاحيات: لا يمنح المستخدم صلاحية لا يملكها هو نفسه.
 * - منع قفل النفس: لا يسحب المستخدم roles.manage من دور يملكه.
 *
 * تستقبل RolePermissionData (مُطبَّعة بلا تكرار) بدل مصفوفة حرة.
 */
class SyncRolePermissionsAction
{
    public function __construct(
        private readonly CreateAuditLogAction $createAuditLog,
    ) {}

    public function execute(Role $role, RolePermissionData $data, User $actor, ?Request $request = null): Role
    {
        $permissions = $data->permissions;

        if ($role->name === 'super-admin') {
            throw ValidationException::withMessages([
                'role' => __('messages.roles.super_admin_locked'),
            ]);
        }

        $notGrantable = array_values(array_diff(
            $permissions,
            $actor->getAllPermissions()->pluck('name')->all(),
        ));

        if ($notGrantable !== []) {
            throw ValidationException::withMessages([
                'permissions' => __('messages.roles.escalation_denied', [
                    'permissions' => implode(', ', $notGrantable),
                ]),
            ]);
        }

        if ($actor->hasRole($role->name) && ! in_array('roles.manage', $permissions, true)) {
            throw ValidationException::withMessages([
                'permissions' => __('messages.roles.cannot_lock_self_out'),
            ]);
        }

        return DB::transaction(function () use ($role, $permissions, $actor, $request): Role {
            $oldPermissions = $role->permissions()->pluck('name')->values()->all();

            $role->syncPermissions($permissions);

            $this->createAuditLog->execute(
                organizationId: (int) $role->organization_id,
                event: 'role.permissions_updated',
                auditableType: Role::class,
                auditableId: $role->id,
                oldValues: ['permissions' => $oldPermissions],
                newValues: ['permissions' => $permissions],
                user: $actor,
                request: $request,
            );

            return $role->refresh();
        });
    }
}
