<?php

namespace App\Http\Controllers;

use App\Models\TeacherAttendance;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * الحضور تلقائي بالكامل (Activity-Based، §1، §5) — لا إنشاء يدوي عبر
 * هذا الـ Controller. Teacher يرى حضوره فقط، Supervisor/Org-Admin/
 * Super-Admin يرى حسب نطاقه (§2، §3).
 */
class TeacherAttendanceController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', TeacherAttendance::class);

        $user = $request->user();

        $query = TeacherAttendance::query();

        if ($user->hasRole('teacher') && ! $user->hasRole('supervisor') && ! $user->hasRole('org-admin') && ! $user->hasRole('super-admin')) {
            $query->where('teacher_id', $user->id);
        }

        return response()->json([
            'data' => $query->latest('date')->paginate(20),
        ]);
    }

    /**
     * صفحة سجل حضور المعلم الحالي (Inertia) — نفس منطق نطاق index أعلاه.
     */
    public function page(Request $request): Response
    {
        $this->authorize('viewAny', TeacherAttendance::class);

        $user = $request->user();

        $query = TeacherAttendance::query();

        if ($user->hasRole('teacher') && ! $user->hasRole('supervisor') && ! $user->hasRole('org-admin') && ! $user->hasRole('super-admin')) {
            $query->where('teacher_id', $user->id);
        }

        return Inertia::render('Teacher/Attendance', [
            'attendances' => $query->latest('date')->paginate(30)->withQueryString(),
        ]);
    }
}
