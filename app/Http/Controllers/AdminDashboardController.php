<?php

namespace App\Http\Controllers;

use App\Models\Circle;
use App\Models\Mosque;
use App\Models\Recitation;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * لوحة تحكم المشرف/مدير المنظمة: إحصائيات عامة مبنية على استعلامات
 * count/sum فعلية (لا تقديرات) — محصورة ضمنياً بمنظمة المستخدم عبر
 * BelongsToOrganization Global Scope (باستثناء super-admin).
 */
class AdminDashboardController extends Controller
{
    public function page(Request $request): Response
    {
        $this->authorize('view-admin-dashboard');

        $today = now()->toDateString();

        return Inertia::render('Admin/Dashboard', [
            'stats' => [
                'mosques_count' => Mosque::query()->count(),
                'circles_count' => Circle::query()->count(),
                'students_count' => StudentProfile::query()->count(),
                'teachers_count' => User::query()->role('teacher')->count(),
                'recitations_today' => Recitation::query()->whereDate('performed_at', $today)->count(),
                'recitations_this_month' => Recitation::query()
                    ->whereMonth('performed_at', now()->month)
                    ->whereYear('performed_at', now()->year)
                    ->count(),
            ],
            'recentRecitations' => Recitation::query()
                ->with(['student.user', 'teacher'])
                ->latest('performed_at')
                ->limit(10)
                ->get(),
        ]);
    }
}
