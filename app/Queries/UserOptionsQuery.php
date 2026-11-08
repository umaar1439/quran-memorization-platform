<?php

declare(strict_types=1);

namespace App\Queries;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

/**
 * خيارات المستخدمين حسب الدور (مثلاً teachers) لقوائم النماذج المنسدلة،
 * بحدّ أقصى صريح يمنع تحميل جدول غير محدود.
 */
class UserOptionsQuery
{
    /**
     * @return Collection<int, User>
     */
    public function byRole(string $role): Collection
    {
        return User::query()
            ->role($role)
            ->orderBy('name')
            ->limit((int) config('tahfeez.filter_options_limit'))
            ->get(['id', 'name']);
    }
}
