<?php

declare(strict_types=1);

namespace App\Actions;

use App\DTOs\AttendanceData;
use App\DTOs\BulkAttendanceData;
use App\Models\Circle;
use App\Models\StudentAttendance;
use App\Models\StudentProfile;
use App\Models\User;
use App\Support\AttendanceDate;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

/**
 * حفظ ورقة الحضور اليومية لحلقة كاملة في عملية واحدة.
 *
 * الفحوص قبل أي كتابة:
 *   1. الفاعل يملك attendance.manage (Fail-Closed عبر AttendancePolicy).
 *   2. الحلقة موجودة، وكل طالب في السجلات ينتمي إليها فعلاً (يمنع تمرير
 *      حلقة مصرَّح بها مع طلاب من حلقة أخرى).
 *   3. كل طالب يمر ببوابة السياسة نفسها (منظمة + ملكية المعلم للحلقة).
 *   4. التاريخ ليس مستقبلياً، ولا تكرار لطالب واحد في الورقة.
 *
 * الكتابة بـ upsert واحد على القيد الفريد (organization_id, student_id, date)
 * بدل كتابة منفصلة لكل طالب: نفس دلالات التصحيح (Idempotent) بأداء ثابت مهما
 * كان حجم الحلقة. لأن upsert لا يُطلق أحداث Eloquent، يُسجَّل تدقيق واحد
 * مُجمَّع (attendance.bulk_recorded) بدل عشرات السجلات المتطابقة — نفس نهج
 * TransferStudentAction مع saveQuietly.
 */
class BulkRecordAttendanceAction
{
    public function __construct(
        private readonly CreateAuditLogAction $createAuditLog,
    ) {}

    /**
     * @return int عدد السجلات المحفوظة
     *
     * @throws AuthorizationException|ValidationException
     */
    public function execute(BulkAttendanceData $data, User $actor): int
    {
        if (Gate::forUser($actor)->denies('create', StudentAttendance::class)) {
            throw new AuthorizationException(__('messages.attendance.forbidden'));
        }

        if ($data->records === []) {
            throw ValidationException::withMessages([
                'records' => __('messages.attendance.empty_records'),
            ]);
        }

        if (count($data->records) > (int) config('tahfeez.bulk_max_records')) {
            throw ValidationException::withMessages([
                'records' => __('messages.attendance.too_many_records'),
            ]);
        }

        $circle = Circle::withoutOrganizationScope()->find($data->circleId);

        if (! $circle) {
            throw ValidationException::withMessages([
                'circle_id' => __('messages.attendance.circle_missing'),
            ]);
        }

        $studentIds = array_map(
            static fn (AttendanceData $record): int => $record->studentId,
            $data->records,
        );

        // الدفاع الثاني بعد قاعدة `distinct` في الـ FormRequest: تكرار الطالب
        // يجعل صفَّي upsert يتنافسان على نفس القيد الفريد داخل جملة واحدة.
        if (count($studentIds) !== count(array_unique($studentIds))) {
            throw ValidationException::withMessages([
                'records' => __('messages.attendance.duplicate_students'),
            ]);
        }

        $students = StudentProfile::withoutOrganizationScope()
            ->with('circle:id,teacher_id,organization_id')
            ->whereIn('id', $studentIds)
            ->get()
            ->keyBy('id');

        foreach ($data->records as $index => $record) {
            $student = $students->get($record->studentId);

            if (! $student) {
                throw ValidationException::withMessages([
                    "records.{$index}.student_id" => __('messages.attendance.student_missing'),
                ]);
            }

            // الطالب يجب أن يكون في الحلقة المطلوبة نفسها، لا في حلقة أخرى
            // يملكها الفاعل — وإلا صار circle_id في السجل تزييفاً للواقع.
            if ($student->circle_id !== $circle->id) {
                throw ValidationException::withMessages([
                    "records.{$index}.student_id" => __('messages.attendance.circle_mismatch'),
                ]);
            }

            if (Gate::forUser($actor)->denies('create', [StudentAttendance::class, $student])) {
                throw ValidationException::withMessages([
                    "records.{$index}.student_id" => __('messages.attendance.forbidden'),
                ]);
            }
        }

        $date = AttendanceDate::resolve($data->date);

        DB::transaction(function () use ($data, $actor, $students, $circle, $date): void {
            $now = now();

            $rows = array_map(static function (AttendanceData $record) use ($students, $actor, $circle, $date, $now): array {
                /** @var StudentProfile $student */
                $student = $students->get($record->studentId);

                return [
                    'organization_id' => $student->organization_id,
                    'student_id' => $student->id,
                    'circle_id' => $circle->id,
                    'mosque_id' => $student->mosque_id,
                    'date' => $date,
                    'status' => $record->status->value,
                    'notes' => $record->notes,
                    'recorded_by' => $actor->id,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }, $data->records);

            StudentAttendance::withoutGlobalScopes()->upsert(
                $rows,
                ['organization_id', 'student_id', 'date'],
                ['circle_id', 'mosque_id', 'status', 'notes', 'recorded_by', 'updated_at'],
            );

            $this->createAuditLog->execute(
                organizationId: $circle->organization_id,
                event: 'attendance.bulk_recorded',
                auditableType: Circle::class,
                auditableId: $circle->id,
                newValues: [
                    'date' => $date,
                    'circle_id' => $circle->id,
                    'records' => count($rows),
                    'statuses' => $this->tally($data->records),
                ],
                user: $actor,
                request: request(),
            );
        });

        return count($data->records);
    }

    /**
     * @param  list<AttendanceData>  $records
     * @return array<string, int>
     */
    private function tally(array $records): array
    {
        $tally = [];

        foreach ($records as $record) {
            $status = $record->status->value;
            $tally[$status] = ($tally[$status] ?? 0) + 1;
        }

        return $tally;
    }
}
