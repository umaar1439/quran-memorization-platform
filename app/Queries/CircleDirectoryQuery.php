<?php

declare(strict_types=1);

namespace App\Queries;

use App\Models\Circle;
use App\Support\SearchTerm;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * استعلام قائمة الحلقات مع فلترة المسجد والبحث، وعدّ الطلاب لكل حلقة
 * عبر withCount (بدل تحميل العلاقة كاملة) — خارج الـ Controller.
 */
class CircleDirectoryQuery
{
    /**
     * @return LengthAwarePaginator<int, Circle>
     */
    public function paginate(?string $search = null, ?int $mosqueId = null): LengthAwarePaginator
    {
        return Circle::query()
            ->with(['mosque', 'teacher'])
            ->withCount('studentProfiles')
            ->when($mosqueId !== null, fn ($query) => $query->where('mosque_id', $mosqueId))
            ->when(
                $search !== null && $search !== '',
                fn ($query) => $query->where('name', 'like', '%'.SearchTerm::escapeLike($search).'%'),
            )
            ->latest()
            ->paginate((int) config('tahfeez.per_page'))
            ->withQueryString();
    }
}
