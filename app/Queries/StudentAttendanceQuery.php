<?php

declare(strict_types=1);

namespace App\Queries;

use App\Enums\AttendanceStatus;
use App\Enums\StudentStatus;
use App\Models\Circle;
use App\Models\StudentAttendance;
use App\Models\StudentProfile;
use App\Models\User;
use App\Policies\AttendancePolicy;
use App\Support\AttendanceDate;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

/**
 * استعلامات وحدة الحضور: ورقة الحضور اليومية وسجل الحضور المفصّل.
 *
 * عدد الاستعلامات في الورقة ثابت (3) مهما كان عدد الطلاب:
 *   1. حلقات الفاعل، 2. طلاب تلك الحلقات، 3. سجلات الحضور لتاريخ محدد.
 * لا استعلام داخل حلقة، ولا Lazy Loading — وهو ما يمنع N+1 في أكثر الشاشات
 * تكراراً (تُفتح لكل حصة).
 *
 * العزل عبر Global Scope (BelongsToOrganization)، ونطاق المعلم يُطبَّق عبر
 * AttendancePolicy::isCircleBoundTeacher — نفس القاعدة المستخدمة في التخويل
 * كي لا تعرض الصفحة حلقات يُرفض حفظ حضورها.
 */
class StudentAttendanceQuery
{
    /**
     * ورقة الحضور اليومية: حلقات الفاعل + طلاب الحلقة المختارة + حالتهم في
     * التاريخ المطلوب (null = لا سجل بعد).
     *
     * @return array{
     *     date: string,
     *     circle_id: int|null,
     *     circles: list<array{id: int, name: string, mosque_name: string|null}>,
     *     rows: list<array{
     *         student_id: int,
     *         student_name: string,
     *         enrollment_number: string,
     *         circle_id: int|null,
     *         status: string|null,
     *         notes: string|null,
     *         attendance_id: int|null,
     *         recorded_at: string|null
     *     }>,
     *     summary: array<string, int>
     * }
     */
    public function dailySheet(User $actor, ?int $circleId = null, ?string $date = null): array
    {
        $date = AttendanceDate::normalise($date);
        $circles = $this->circlesFor($actor);

        // بلا اختيار صريح: أول حلقة للفاعل. الورقة دائماً لحلقة واحدة كي يكون
        // circle_id معرَّفاً عند الحفظ (BulkAttendanceRequest يشترطه).
        $selected = $circleId !== null ? $circles->firstWhere('id', $circleId) : $circles->first();

        $circleOptions = $circles
            ->map(fn (Circle $circle): array => [
                'id' => $circle->id,
                'name' => $circle->name,
                'mosque_name' => $circle->mosque?->name,
            ])
            ->values()
            ->all();

        // حلقة غير موجودة أو خارج نطاق الفاعل: ورقة فارغة بلا رسالة تكشف
        // وجود الحلقة (لا نُعيد بيانات معلم آخر ولا نؤكد وجود المعرّف).
        if ($circleId !== null && $selected === null) {
            return $this->sheetPayload($date, null, $circleOptions, [], $this->emptySummary());
        }

        $circleIds = $selected !== null ? [$selected->id] : [];

        if ($circleIds === []) {
            return $this->sheetPayload($date, $selected?->id, $circleOptions, [], $this->emptySummary());
        }

        $students = StudentProfile::query()
            ->with('user:id,name')
            ->whereIn('circle_id', $circleIds)
            ->where('status', StudentStatus::Active->value)
            ->orderBy('enrollment_number')
            ->limit((int) config('tahfeez.filter_options_limit'))
            ->get(['id', 'user_id', 'circle_id', 'enrollment_number']);

        $records = StudentAttendance::query()
            ->whereIn('student_id', $students->pluck('id'))
            ->whereDate('date', $date)
            ->get(['id', 'student_id', 'status', 'notes', 'updated_at'])
            ->keyBy('student_id');

        $summary = $this->emptySummary();
        $rows = [];

        foreach ($students as $student) {
            $record = $records->get($student->id);
            $status = $record?->status?->value;

            $rows[] = [
                'student_id' => $student->id,
                'student_name' => $student->user?->name ?? '',
                'enrollment_number' => $student->enrollment_number,
                'circle_id' => $student->circle_id,
                'status' => $status,
                'notes' => $record?->notes,
                'attendance_id' => $record?->id,
                'recorded_at' => $record?->updated_at?->toIso8601String(),
            ];

            $summary[$status ?? 'unrecorded']++;
        }

        return $this->sheetPayload($date, $selected?->id, $circleOptions, $rows, $summary);
    }

    /**
     * سجل الحضور المفصّل (شاشة الإدارة) مع فلترة بالحلقة/الطالب/الحالة/التاريخ.
     * العلاقات المستخدمة في الجدول تُحمَّل مسبقاً دائماً، فلا N+1 في العرض.
     *
     * @return LengthAwarePaginator<int, StudentAttendance>
     */
    public function history(
        User $actor,
        ?int $circleId = null,
        ?int $studentId = null,
        ?string $status = null,
        ?string $date = null,
    ): LengthAwarePaginator {
        return StudentAttendance::query()
            ->with([
                'student:id,user_id,enrollment_number',
                'student.user:id,name',
                'circle:id,name',
                'recorder:id,name',
            ])
            ->when(
                AttendancePolicy::isCircleBoundTeacher($actor),
                fn ($query) => $query->whereIn('circle_id', $this->circleIdsFor($actor)),
            )
            ->when($circleId !== null, fn ($query) => $query->where('circle_id', $circleId))
            ->when($studentId !== null, fn ($query) => $query->where('student_id', $studentId))
            ->when($status !== null, fn ($query) => $query->where('status', $status))
            ->when($date !== null, fn ($query) => $query->whereDate('date', $date))
            ->latest('date')
            ->latest('id')
            ->paginate((int) config('tahfeez.per_page_large'))
            ->withQueryString();
    }

    /**
     * حلقات الفاعل: المعلم يرى حلقاته المسندة فقط، والأدوار الإدارية ترى كل
     * حلقات منظمتها (Global Scope)، بسقف صريح لعدد الخيارات.
     *
     * @return Collection<int, Circle>
     */
    private function circlesFor(User $actor): Collection
    {
        return Circle::query()
            ->with('mosque:id,name')
            ->when(
                AttendancePolicy::isCircleBoundTeacher($actor),
                fn ($query) => $query->where('teacher_id', $actor->id),
            )
            ->orderBy('name')
            ->limit((int) config('tahfeez.filter_options_limit'))
            ->get(['id', 'name', 'mosque_id']);
    }

    /**
     * @return list<int>
     */
    private function circleIdsFor(User $actor): array
    {
        // نفس مصدر النطاق المستخدم في الورقة — قاعدة واحدة لا نسختان تنحرفان.
        return $this->circlesFor($actor)->pluck('id')->all();
    }

    /**
     * @return array<string, int>
     */
    private function emptySummary(): array
    {
        $summary = [];

        foreach (AttendanceStatus::cases() as $status) {
            $summary[$status->value] = 0;
        }

        $summary['unrecorded'] = 0;

        return $summary;
    }

    /**
     * @param  list<array{id: int, name: string, mosque_name: string|null}>  $circles
     * @param  list<array<string, mixed>>  $rows
     * @param  array<string, int>  $summary
     * @return array{
     *     date: string,
     *     circle_id: int|null,
     *     circles: list<array{id: int, name: string, mosque_name: string|null}>,
     *     rows: list<array<string, mixed>>,
     *     summary: array<string, int>
     * }
     */
    private function sheetPayload(string $date, ?int $circleId, array $circles, array $rows, array $summary): array
    {
        return [
            'date' => $date,
            'circle_id' => $circleId,
            'circles' => $circles,
            'rows' => $rows,
            'summary' => $summary,
        ];
    }
}
