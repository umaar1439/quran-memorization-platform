<?php

declare(strict_types=1);

namespace App\Queries;

use App\Models\Recitation;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

/**
 * بيانات صفحة "تسميع سريع" للمعلم: طلاب حلقاته + آخر تسميعاته.
 * كل قائمة بحدّ أقصى صريح لمنع تحميل بيانات غير محدودة.
 */
class QuickRecitationQuery
{
    /**
     * @return Collection<int, StudentProfile>
     */
    public function students(User $user): Collection
    {
        return StudentProfile::query()
            ->with('user')
            ->when(
                $user->hasRole('teacher'),
                fn ($query) => $query->whereHas('circle', fn ($circle) => $circle->where('teacher_id', $user->id)),
            )
            ->limit((int) config('tahfeez.filter_options_limit'))
            ->get();
    }

    /**
     * @return Collection<int, Recitation>
     */
    public function recent(User $user, int $limit = 15): Collection
    {
        return Recitation::query()
            ->with(['errors', 'student.user'])
            ->where('teacher_id', $user->id)
            ->latest('performed_at')
            ->limit($limit)
            ->get();
    }
}
