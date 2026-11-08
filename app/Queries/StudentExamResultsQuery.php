<?php

declare(strict_types=1);

namespace App\Queries;

use App\Models\ExamResult;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * نتائج امتحانات الطالب الحالي — النطاق محدد ضمنياً بـ student_id.
 */
class StudentExamResultsQuery
{
    /**
     * @return LengthAwarePaginator<int, ExamResult>
     */
    public function paginate(int $studentId): LengthAwarePaginator
    {
        return ExamResult::query()
            ->with('exam')
            ->where('student_id', $studentId)
            ->latest('created_at')
            ->paginate((int) config('tahfeez.per_page'))
            ->withQueryString();
    }
}
