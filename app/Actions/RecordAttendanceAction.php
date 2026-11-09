<?php

declare(strict_types=1);

namespace App\Actions;

use App\DTOs\AttendanceData;
use App\Models\StudentAttendance;
use App\Models\StudentProfile;
use App\Models\User;
use App\Support\AttendanceDate;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

/**
 * تسجيل/تصحيح حضور طالب واحد في يوم واحد.
 *
 * الشروط المفروضة هنا (Defense-in-Depth فوق FormRequest/Policy):
 *   1. الفاعل يملك attendance.manage ولديه سياق منظمة (Fail-Closed).
 *   2. الطالب موجود وغير محذوف، وينتمي لمنظمة الفاعل.
 *   3. المعلم لا يسجّل حضور طالب خارج حلقاته (IDOR Defense).
 *   4. التاريخ ليس مستقبلياً.
 *
 * الكتابة بـ updateOrCreate على القيد الفريد (organization_id, student_id,
 * date): إعادة الإرسال تصحّح السجل القائم ولا تُنشئ حضوراً مزدوجاً — وهو ما
 * يجعل العملية Idempotent لورشة المزامنة دون اتصال.
 */
class RecordAttendanceAction
{
    public function execute(AttendanceData $data, User $actor): StudentAttendance
    {
        $student = StudentProfile::withoutOrganizationScope()
            ->with('circle:id,teacher_id,organization_id')
            ->find($data->studentId);

        if (! $student) {
            throw ValidationException::withMessages([
                'student_id' => __('messages.attendance.student_missing'),
            ]);
        }

        // نفس بوابة AttendancePolicy التي استخدمها StoreAttendanceRequest —
        // لا اعتماد حصري على طبقة HTTP أمام الـ Action.
        if (Gate::forUser($actor)->denies('create', [StudentAttendance::class, $student])) {
            throw ValidationException::withMessages([
                'student_id' => $this->denialMessage($actor, $student),
            ]);
        }

        $date = AttendanceDate::resolve($data->date);

        return DB::transaction(function () use ($data, $actor, $student, $date): StudentAttendance {
            // قفل صف الطالب يُسلسِل طلبين متزامنين لنفس (الطالب، اليوم)، فلا
            // يفشل أحدهما بمخالفة القيد الفريد بين SELECT وINSERT في
            // updateOrCreate (نفس نهج TransferStudentAction).
            StudentProfile::withoutOrganizationScope()
                ->whereKey($student->id)
                ->lockForUpdate()
                ->first();

            return StudentAttendance::query()->updateOrCreate(
                [
                    'organization_id' => $student->organization_id,
                    'student_id' => $student->id,
                    'date' => $date,
                ],
                [
                    'circle_id' => $student->circle_id,
                    'mosque_id' => $student->mosque_id,
                    'status' => $data->status,
                    'notes' => $data->notes,
                    'recorded_by' => $actor->id,
                ],
            );
        });
    }

    /**
     * رسالة رفض دقيقة بدل 403 عام: تفرّق بين «مستخدم آخر منظمة» و«طالب خارج
     * حلقات المعلم» و«صلاحية ناقصة» — كلها تؤدي إلى نفس المنع.
     */
    private function denialMessage(User $actor, StudentProfile $student): string
    {
        if ($actor->organization_id === null || $actor->organization_id !== $student->organization_id) {
            return __('messages.attendance.student_other_org');
        }

        if (! $actor->can('attendance.manage')) {
            return __('messages.attendance.forbidden');
        }

        return __('messages.attendance.not_your_circle');
    }
}
