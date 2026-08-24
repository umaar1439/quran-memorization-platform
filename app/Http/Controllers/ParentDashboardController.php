<?php

namespace App\Http\Controllers;

use App\Models\Guardian;
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
        private readonly \App\Actions\CalculateStudentAnalyticsAction $calculateAnalytics,
    ) {}

    public function page(Request $request): Response
    {
        $guardian = $request->user()->guardian;

        abort_if(! $guardian, 404, 'لا يوجد ملف ولي أمر مرتبط بهذا المستخدم.');

        $students = $guardian->students()->with(['user', 'mosque', 'circle'])->get();

        $summaries = $students->map(function ($student) {
            $analytics = $this->calculateAnalytics->execute($student);

            return [
                'student' => $student,
                'overall_progress' => $analytics['overall_progress'],
                'attendance_rate' => $analytics['attendance_rate'],
            ];
        });

        return Inertia::render('Parent/Dashboard', [
            'children' => $summaries,
        ]);
    }
}
