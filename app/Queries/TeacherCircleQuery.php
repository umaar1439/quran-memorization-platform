<?php

declare(strict_types=1);

namespace App\Queries;

use App\Models\Circle;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

/**
 * حلقات المعلم الحالي مع طلابها — النطاق محدد ضمنياً بـ teacher_id،
 * وبسقف صريح حتى لا تُحمَّل قائمة غير محدودة في صفحة واحدة.
 */
class TeacherCircleQuery
{
    /**
     * @return Collection<int, Circle>
     */
    public function forTeacher(User $user): Collection
    {
        return Circle::query()
            ->with(['mosque', 'studentProfiles.user'])
            ->where('teacher_id', $user->id)
            ->orderBy('name')
            ->limit((int) config('tahfeez.filter_options_limit'))
            ->get();
    }
}
