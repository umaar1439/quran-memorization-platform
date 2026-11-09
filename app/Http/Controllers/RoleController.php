<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\SyncRolePermissionsAction;
use App\Http\Requests\SyncRolePermissionsRequest;
use App\Models\User;
use App\Queries\RoleMatrixQuery;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

/**
 * شاشة «الأدوار والصلاحيات» (/admin/roles).
 *
 * تعرض صلاحيات كل دور داخل منظمة المستخدم وتسمح بمزامنتها. التفويض بالصلاحية
 * لا بالدور (نفس مبدأ الحلقات): roles.view للعرض وroles.manage للتعديل عبر
 * RolePolicy. الأدوار team-scoped، لذا لا يظهر أو يُعدَّل دور منظمة أخرى.
 *
 * قراءة المصفوفة في RoleMatrixQuery، والتحقق/التطبيع في
 * SyncRolePermissionsRequest::toData()، والقواعد في SyncRolePermissionsAction.
 */
class RoleController extends Controller
{
    public function __construct(
        private readonly RoleMatrixQuery $matrix,
        private readonly SyncRolePermissionsAction $syncPermissions,
    ) {}

    public function page(Request $request): Response
    {
        $this->authorize('viewAny', Role::class);

        /** @var User $actor */
        $actor = $request->user();

        return Inertia::render('Admin/Roles/Index', [
            'roles' => $this->matrix->forActor($actor),
            'permissions' => $this->matrix->permissionNames(),
        ]);
    }

    public function update(SyncRolePermissionsRequest $request, Role $role): RedirectResponse
    {
        $this->syncPermissions->execute($role, $request->toData(), $request->user(), $request);

        return back()->with('success', __('messages.roles.updated'));
    }
}
