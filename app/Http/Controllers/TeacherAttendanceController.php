<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Resources\TeacherAttendanceResource;
use App\Models\TeacherAttendance;
use App\Queries\TeacherAttendanceQuery;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Inertia\Inertia;
use Inertia\Response;

/**
 * الحضور تلقائي بالكامل (Activity-Based، §1، §5) — لا إنشاء يدوي عبر
 * هذا الـ Controller. Teacher يرى حضوره فقط، Supervisor/Org-Admin/
 * Super-Admin يرى حسب نطاقه (§2، §3).
 */
class TeacherAttendanceController extends Controller
{
    public function __construct(
        private readonly TeacherAttendanceQuery $attendances,
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', TeacherAttendance::class);

        return TeacherAttendanceResource::collection(
            $this->attendances->paginate($request->user()),
        );
    }

    /**
     * صفحة سجل حضور المعلم الحالي (Inertia) — نفس منطق نطاق index أعلاه.
     */
    public function page(Request $request): Response
    {
        $this->authorize('viewAny', TeacherAttendance::class);

        return Inertia::render('Teacher/Attendance', [
            'attendances' => $this->attendances->paginate(
                $request->user(),
                (int) config('tahfeez.per_page_large'),
                withQueryString: true,
            ),
        ]);
    }
}
