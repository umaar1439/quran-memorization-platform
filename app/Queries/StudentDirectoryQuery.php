<?php

declare(strict_types=1);

namespace App\Queries;

use App\Models\StudentProfile;
use App\Support\SearchTerm;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * استعلام قائمة الطلاب مع فلترة البحث والحلقة، وإلزام Eager Loading
 * للعلاقات المستخدمة في الواجهة لمنع N+1.
 */
class StudentDirectoryQuery
{
    /**
     * @return LengthAwarePaginator<int, StudentProfile>
     */
    public function paginate(?string $search = null, ?int $circleId = null): LengthAwarePaginator
    {
        return StudentProfile::query()
            ->with(['user', 'circle', 'mosque'])
            ->when(
                $search !== null && $search !== '',
                fn ($query) => $query->whereHas(
                    'user',
                    fn ($userQuery) => $userQuery->where('name', 'like', '%'.SearchTerm::escapeLike($search).'%'),
                ),
            )
            ->when($circleId !== null, fn ($query) => $query->where('circle_id', $circleId))
            ->latest()
            ->paginate((int) config('tahfeez.per_page'))
            ->withQueryString();
    }

    /**
     * تحميل السياق الكامل لملف طالب واحد (المستخدم/المسجد/الحلقة) — Eager
     * Loading صريح لمنع N+1 في صفحات العرض واللوحات.
     */
    public function withContext(StudentProfile $student): StudentProfile
    {
        return $student->load(['user', 'mosque', 'circle']);
    }
}
