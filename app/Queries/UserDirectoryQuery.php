<?php

declare(strict_types=1);

namespace App\Queries;

use App\Models\User;
use App\Support\RoleHierarchy;
use App\Support\SearchTerm;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

/**
 * استعلام قائمة المستخدمين مع فلترة البحث والدور. الدور يُطابق فقط
 * القيم المسموح بها في ROLES حتى لا يُمرَّر أي نص عشوائي إلى scope.
 *
 * عزل المستأجر: User لا يستخدم BelongsToOrganization (organization_id
 * اختياري لـ super-admin)، لذا يجب تقييد القائمة صراحةً بمنظمة الفاعل —
 * بدون ذلك تكشف الشاشة مستخدمي كل المنظمات (Cross-Tenant Leak).
 */
class UserDirectoryQuery
{
    /**
     * الأدوار القابلة للإسناد من واجهة إدارة المستخدمين.
     *
     * @var list<string>
     */
    public const ROLES = ['org-admin', 'supervisor', 'teacher', 'student', 'parent'];

    /**
     * @return LengthAwarePaginator<int, User>
     */
    public function paginate(User $actor, ?string $search = null, ?string $role = null): LengthAwarePaginator
    {
        return User::query()
            ->with('roles')
            ->when(
                ! $actor->hasRole(RoleHierarchy::SUPER_ADMIN),
                // whereNotNull + where(organization_id) تعني: فاعل بلا منظمة
                // يرى قائمة فارغة (Fail-Closed) لا كل المستخدمين.
                fn (Builder $query): Builder => $query
                    ->whereNotNull('organization_id')
                    ->where('organization_id', $actor->organization_id),
            )
            ->when(
                $search !== null && $search !== '',
                fn ($query) => $query->where('name', 'like', '%'.SearchTerm::escapeLike($search).'%'),
            )
            ->when(
                $role !== null && in_array($role, self::ROLES, true),
                fn ($query) => $query->role($role),
            )
            ->latest()
            ->paginate((int) config('tahfeez.per_page'))
            ->withQueryString();
    }
}
