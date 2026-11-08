<?php

declare(strict_types=1);

namespace App\Queries;

use App\Models\TeacherAttendance;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * استعلام حضور المعلمين: المعلم يرى حضوره فقط، أما المشرف/مدير المنظمة/
 * مدير النظام فيرون النطاق الكامل المتاح لهم عبر Global Scope.
 */
class TeacherAttendanceQuery
{
    /**
     * @return LengthAwarePaginator<int, TeacherAttendance>
     */
    public function paginate(
        User $user,
        ?int $perPage = null,
        bool $withQueryString = false,
    ): LengthAwarePaginator {
        $attendances = TeacherAttendance::query()
            ->when($this->isTeacherOnly($user), fn ($query) => $query->where('teacher_id', $user->id))
            ->latest('date')
            ->paginate($perPage ?? (int) config('tahfeez.per_page'));

        return $withQueryString ? $attendances->withQueryString() : $attendances;
    }

    /**
     * teacher فقط بلا أي دور إشرافي أعلى.
     */
    private function isTeacherOnly(User $user): bool
    {
        return $user->hasRole('teacher')
            && ! $user->hasRole('supervisor')
            && ! $user->hasRole('org-admin')
            && ! $user->hasRole('super-admin');
    }
}
