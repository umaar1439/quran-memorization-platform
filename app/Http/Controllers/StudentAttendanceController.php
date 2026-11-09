<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\BulkRecordAttendanceAction;
use App\Actions\RecordAttendanceAction;
use App\Enums\AttendanceStatus;
use App\Http\Requests\BulkAttendanceRequest;
use App\Http\Requests\StoreAttendanceRequest;
use App\Models\StudentAttendance;
use App\Queries\StudentAttendanceQuery;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * وحدة حضور الطلاب اليومي.
 *
 * الـ Controller رقيق: التخويل عبر AttendancePolicy (viewAny للقراءة،
 * وcreate عبر الـ FormRequests/الـ Actions للكتابة)، القراءة من Query،
 * والكتابة إلى Actions. لا استعلامات ولا منطق عمل هنا.
 */
class StudentAttendanceController extends Controller
{
    public function __construct(
        private readonly RecordAttendanceAction $recordAttendance,
        private readonly BulkRecordAttendanceAction $bulkRecordAttendance,
        private readonly StudentAttendanceQuery $attendances,
    ) {}

    /**
     * صفحة المعلم: ورقة الحضور اليومية لحلقاته فقط.
     */
    public function teacherPage(Request $request): Response
    {
        $this->authorize('viewAny', StudentAttendance::class);

        return Inertia::render('Teacher/AttendanceSheet', [
            'sheet' => $this->attendances->dailySheet(
                actor: $request->user(),
                circleId: $this->requestCircleId($request),
                date: $this->requestDate($request),
            ),
        ]);
    }

    /**
     * صفحة الإدارة: ورقة الحضور للحلقة/التاريخ المختارين + سجل الحضور المفصّل
     * بنفس الفلاتر، فيتحقق المشرف من الحفظ فوراً بعد اعتماده.
     */
    public function page(Request $request): Response
    {
        $this->authorize('viewAny', StudentAttendance::class);

        $circleId = $this->requestCircleId($request);
        $date = $this->requestDate($request);
        $status = AttendanceStatus::tryFrom($request->string('status')->toString());

        return Inertia::render('Admin/Attendances/Index', [
            'sheet' => $this->attendances->dailySheet(
                actor: $request->user(),
                circleId: $circleId,
                date: $date,
            ),
            'attendances' => $this->attendances->history(
                actor: $request->user(),
                circleId: $circleId,
                studentId: $request->filled('student_id') ? $request->integer('student_id') : null,
                status: $status?->value,
                date: $date,
            ),
            'filters' => $request->only('circle_id', 'student_id', 'status', 'date'),
        ]);
    }

    public function store(StoreAttendanceRequest $request): RedirectResponse
    {
        $attendance = $this->recordAttendance->execute($request->toData(), $request->user());

        return back()->with('success', $attendance->wasRecentlyCreated
            ? __('messages.attendance.created')
            : __('messages.attendance.updated'));
    }

    public function bulk(BulkAttendanceRequest $request): RedirectResponse
    {
        $count = $this->bulkRecordAttendance->execute($request->toData(), $request->user());

        return back()->with('success', __('messages.attendance.bulk_saved', ['count' => $count]));
    }

    private function requestCircleId(Request $request): ?int
    {
        return $request->filled('circle_id') ? $request->integer('circle_id') : null;
    }

    /**
     * تاريخ صالح بصيغة Y-m-d أو null — معامل قراءة غير صالح يُتجاهَل بدل
     * إسقاط الصفحة بخطأ تحقق (التاريخ يُتحقق بصرامة عند الكتابة فقط).
     */
    private function requestDate(Request $request): ?string
    {
        $value = $request->string('date')->toString();

        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) === 1 ? $value : null;
    }
}
