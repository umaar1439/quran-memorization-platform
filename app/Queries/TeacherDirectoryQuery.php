<?php

declare(strict_types=1);

namespace App\Queries;

use App\Models\TeacherProfile;
use App\Support\SearchTerm;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * استعلام قائمة المعلمين (مع فلترة البحث) — منطق قراءة قابل لإعادة الاستخدام
 * خارج الـ Controller، مع Eager Loading إلزامي لمنع N+1.
 */
class TeacherDirectoryQuery
{
    /**
     * @return LengthAwarePaginator<int, TeacherProfile>
     */
    public function paginate(?string $search = null): LengthAwarePaginator
    {
        return TeacherProfile::query()
            ->with(['user', 'user.circlesTaught'])
            ->when(
                $search !== null && $search !== '',
                fn ($query) => $query->whereHas(
                    'user',
                    fn ($userQuery) => $userQuery->where('name', 'like', '%'.SearchTerm::escapeLike($search).'%'),
                ),
            )
            ->latest()
            ->paginate((int) config('tahfeez.per_page'))
            ->withQueryString();
    }
}
