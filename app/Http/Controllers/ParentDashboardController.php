<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Queries\ParentChildrenQuery;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * صفحة "أبنائي" (Parent): تعرض الأبناء المرتبطين بولي الأمر الحالي عبر
 * guardian_student، مع ملخص تقدم كل ابن (يُحسب فعلياً عبر Action نفسه
 * المستخدم في StudentDashboardController لتجنّب ازدواجية منطق الحساب).
 */
class ParentDashboardController extends Controller
{
    public function __construct(
        private readonly ParentChildrenQuery $children,
    ) {}

    public function page(Request $request): Response
    {
        $guardian = $request->user()->guardian;

        abort_if(! $guardian, 404, __('messages.students.missing_guardian_profile'));

        return Inertia::render('Parent/Dashboard', [
            'children' => $this->children->summaries($guardian),
        ]);
    }
}
