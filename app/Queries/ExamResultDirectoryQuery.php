<?php

declare(strict_types=1);

namespace App\Queries;

use App\Models\Exam;
use App\Models\ExamResult;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * نتائج امتحان محدد لصفحة الاعتماد ورصد الدرجات.
 */
class ExamResultDirectoryQuery
{
    /**
     * @return LengthAwarePaginator<int, ExamResult>
     */
    public function forExam(Exam $exam): LengthAwarePaginator
    {
        return $exam->results()
            ->with('student.user')
            ->latest()
            ->paginate((int) config('tahfeez.per_page_large'))
            ->withQueryString();
    }
}
