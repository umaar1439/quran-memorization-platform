<?php

namespace App\Http\Controllers;

use App\Actions\CalculateStudentAnalyticsAction;
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

        return response()->json([
            'data' => $this->calculateAnalytics->execute($student),
        ]);
    }

    /**
     * صفحة لوحة تحكم الطالب الحالي المسجَّل دخوله (Inertia). يُستخدم
     * studentProfile الخاص بالمستخدم الحالي مباشرة — لا route parameter.
     */
    public function page(Request $request): Response
    {
        $student = $request->user()->studentProfile;

        abort_if(! $student, 404, 'لا يوجد ملف طالب مرتبط بهذا المستخدم.');

        $this->authorize('view', $student);

        return Inertia::render('Student/Dashboard', [
            'analytics' => $this->calculateAnalytics->execute($student),
            'student' => $student->load(['mosque', 'circle']),
        ]);
    }
}
