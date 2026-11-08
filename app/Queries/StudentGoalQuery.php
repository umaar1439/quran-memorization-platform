<?php

declare(strict_types=1);

namespace App\Queries;

use App\Models\StudentGoal;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * استعلام أهداف الطلاب: نطاق الدور (معلم/طالب/ولي أمر) + فلتر طالب اختياري.
 * منفصل عن الـ Controller لتفادي تكرار منطق النطاق بين index() وpage().
 */
class StudentGoalQuery
{
    /**
     * @return LengthAwarePaginator<int, StudentGoal>
     */
    public function paginate(User $user, ?int $studentId = null, bool $withQueryString = false): LengthAwarePaginator
    {
        $query = StudentGoal::query()->with('surah');

        // نطاق واحد فقط لكل مستخدم (if/elseif) كما في السلوك الأصلي.
        if ($this->isTeacherOnly($user)) {
            $query->whereHas(
                'student.circle',
                fn ($circle) => $circle->where('teacher_id', $user->id),
            );
        } elseif ($user->hasRole('student')) {
            $query->where('student_id', $user->studentProfile?->id);
        } elseif ($user->hasRole('parent')) {
            $guardian = $user->guardian;

            $query->whereIn(
                'student_id',
                $guardian ? $guardian->students()->pluck('student_profiles.id') : collect(),
            );
        }

        if ($studentId !== null) {
            $query->where('student_id', $studentId);
        }

        $goals = $query
            ->latest('period_start')
            ->paginate((int) config('tahfeez.per_page'));

        return $withQueryString ? $goals->withQueryString() : $goals;
    }

    /**
     * @return LengthAwarePaginator<int, StudentGoal>
     */
    public function forStudent(int $studentId): LengthAwarePaginator
    {
        return StudentGoal::query()
            ->with('surah')
            ->where('student_id', $studentId)
            ->latest('period_start')
            ->paginate((int) config('tahfeez.per_page'))
            ->withQueryString();
    }

    private function isTeacherOnly(User $user): bool
    {
        return $user->hasRole('teacher')
            && ! $user->hasRole('supervisor')
            && ! $user->hasRole('org-admin')
            && ! $user->hasRole('super-admin');
    }
}
