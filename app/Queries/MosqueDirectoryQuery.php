<?php

declare(strict_types=1);

namespace App\Queries;

use App\Models\Mosque;
use App\Support\SearchTerm;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * استعلام قائمة المساجد مع فلترة البحث وعدّ الحلقات — منطق قراءة خارج
 * الـ Controller، مع Eager Counting لمنع N+1 في عمود "عدد الحلقات".
 */
class MosqueDirectoryQuery
{
    /**
     * @return LengthAwarePaginator<int, Mosque>
     */
    public function paginate(?string $search = null): LengthAwarePaginator
    {
        return Mosque::query()
            ->withCount('circles')
            ->when(
                $search !== null && $search !== '',
                fn ($query) => $query->where('name', 'like', '%'.SearchTerm::escapeLike($search).'%'),
            )
            ->latest()
            ->paginate((int) config('tahfeez.per_page'))
            ->withQueryString();
    }
}
