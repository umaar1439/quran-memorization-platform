<?php

declare(strict_types=1);

namespace App\Queries;

use App\Models\User;
use App\Support\SearchTerm;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * استعلام قائمة المستخدمين مع فلترة البحث والدور. الدور يُطابق فقط
 * القيم المسموح بها في ROLES حتى لا يُمرَّر أي نص عشوائي إلى scope.
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
    public function paginate(?string $search = null, ?string $role = null): LengthAwarePaginator
    {
        return User::query()
            ->with('roles')
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
