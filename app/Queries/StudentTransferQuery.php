<?php

declare(strict_types=1);

namespace App\Queries;

use App\Models\StudentProfile;
use App\Models\StudentTransfer;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

/**
 * سجل نقل الطلاب + خيارات الطلاب المستخدمة في نموذج النقل، بحد أقصى صريح
 * لخيارات النموذج بدل تحميل جدول الطلاب كاملاً.
 */
class StudentTransferQuery
{
    /**
     * @return LengthAwarePaginator<int, StudentTransfer>
     */
    public function paginate(?int $studentId = null, bool $withQueryString = false): LengthAwarePaginator
    {
        $transfers = StudentTransfer::query()
            ->with(['student.user', 'fromMosque', 'toMosque'])
            ->when($studentId !== null, fn ($query) => $query->where('student_id', $studentId))
            ->latest('transferred_at')
            ->paginate((int) config('tahfeez.per_page'));

        return $withQueryString ? $transfers->withQueryString() : $transfers;
    }

    /**
     * @return Collection<int, StudentProfile>
     */
    public function studentOptions(): Collection
    {
        return StudentProfile::query()
            ->with('user')
            ->orderBy('id')
            ->limit((int) config('tahfeez.filter_options_limit'))
            ->get(['id', 'user_id', 'mosque_id', 'circle_id']);
    }
}
