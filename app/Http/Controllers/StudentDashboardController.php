<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\CalculateStudentAnalyticsAction;
use App\Http\Resources\StudentAnalyticsResource;
use App\Models\StudentProfile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * index: يُرجع تحليلات الطالب الحالي (نفسه) أو لابن ولي الأمر المصرَّح
 * به، أو أي طالب ضمن نطاق Teacher/Supervisor/Org-Admin (§2، §3). يُفوّض
 * التحقق من الصلاحية لـ StudentProfilePolicy::view (نفس النطاق المستخدم
 * لعرض بيانات الطالب نفسها).
 */
class StudentDashboardController extends Controller
{
    public function __construct(
        private readonly CalculateStudentAnalyticsAction $calculateAnalytics,
    ) {}

    public function index(Request $request, StudentProfile $student): JsonResponse
    {
        $this->authorize('view', $student);

        return (new StudentAnalyticsResource($this->calculateAnalytics->execute($student)))->response();
    }

    /**
     * صفحة لوحة تحكم الطالب الحالي المسجَّل دخوله (Inertia). يُستخدم
     * studentProfile الخاص بالمستخدم الحالي مباشرة — لا route parameter.
     */
    public function page(Request $request): Response
    {
        $student = $request->user()->studentProfile;

        abort_if(! $student, 404, __('messages.students.missing_profile'));

        $this->authorize('view', $student);

        return Inertia::render('Student/Dashboard', [
            'analytics' => $this->calculateAnalytics->execute($student),
            'student' => $student->load(['mosque', 'circle']),
        ]);
    }

    /**
     * صفحة عرض ملف + لوحة تحكم طالب محدد (للمدير/المشرف/المعلم ضمن صلاحياته).
     * التحقق من الصلاحية عبر StudentProfilePolicy::view (نفس نطاق عرض البيانات):
     * مدير/مشرف = وصول كامل داخل المنظومة، معلم = طلاب حلقاته فقط.
     */
    public function show(Request $request, StudentProfile $student): Response
    {
        $this->authorize('view', $student);

        return Inertia::render('Students/Show', [
            'student' => $student->load(['user', 'mosque', 'circle']),
            'analytics' => $this->calculateAnalytics->execute($student),
        ]);
    }
}
