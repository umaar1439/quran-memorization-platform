<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Queries\AdminDashboardQuery;
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
    public function __construct(
        private readonly AdminDashboardQuery $dashboard,
    ) {}

    public function page(Request $request): Response
    {
        $this->authorize('view-admin-dashboard');

        return Inertia::render('Admin/Dashboard', [
            'stats' => $this->dashboard->stats(),
            'recentRecitations' => $this->dashboard->recentRecitations(),
        ]);
    }
}
