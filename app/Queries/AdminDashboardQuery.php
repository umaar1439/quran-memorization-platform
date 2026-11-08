<?php

declare(strict_types=1);

namespace App\Queries;

use App\Models\Circle;
use App\Models\Mosque;
use App\Models\Recitation;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

/**
 * بيانات لوحة تحكم المشرف/مدير المنظمة: إحصائيات مبنية على count فعلية
 * (لا تقديرات) محصورة ضمنياً بمنظمة المستخدم عبر Global Scope.
 */
class AdminDashboardQuery
{
    /**
     * @return array<string, int>
     */
    public function stats(): array
    {
        $today = now()->toDateString();

        return [
            'mosques_count' => Mosque::query()->count(),
            'circles_count' => Circle::query()->count(),
            'students_count' => StudentProfile::query()->count(),
            'teachers_count' => User::query()->role('teacher')->count(),
            'recitations_today' => Recitation::query()->whereDate('performed_at', $today)->count(),
            'recitations_this_month' => Recitation::query()
                ->whereMonth('performed_at', now()->month)
                ->whereYear('performed_at', now()->year)
                ->count(),
        ];
    }

    /**
     * @return Collection<int, Recitation>
     */
    public function recentRecitations(int $limit = 10): Collection
    {
        return Recitation::query()
            ->with(['student.user', 'teacher'])
            ->latest('performed_at')
            ->limit($limit)
            ->get();
    }
}
