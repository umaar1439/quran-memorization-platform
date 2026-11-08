<?php

declare(strict_types=1);

namespace App\Queries;

use App\Models\Exam;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * استعلام قائمة الامتحانات مع فلترة الحلقة/المسجد، وEager Loading للمسجد
 * والحلقة (المستخدمين في الجدول) لمنع N+1.
 */
class ExamDirectoryQuery
{
    /**
     * @return LengthAwarePaginator<int, Exam>
     */
    public function paginate(?int $circleId = null, ?int $mosqueId = null): LengthAwarePaginator
    {
        return Exam::query()
            ->with(['mosque', 'circle'])
            ->when($circleId !== null, fn ($query) => $query->where('circle_id', $circleId))
            ->when($mosqueId !== null, fn ($query) => $query->where('mosque_id', $mosqueId))
            ->latest('scheduled_at')
            ->paginate((int) config('tahfeez.per_page'));
    }
}
