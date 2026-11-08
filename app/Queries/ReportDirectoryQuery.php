<?php

declare(strict_types=1);

namespace App\Queries;

use App\Models\Report;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * استعلام قائمة التقارير ضمن نطاق المستخدم (Global Scope للمنظمة).
 */
class ReportDirectoryQuery
{
    /**
     * @return LengthAwarePaginator<int, Report>
     */
    public function paginate(bool $withQueryString = false): LengthAwarePaginator
    {
        $reports = Report::query()
            ->latest('created_at')
            ->paginate((int) config('tahfeez.per_page'));

        return $withQueryString ? $reports->withQueryString() : $reports;
    }
}
