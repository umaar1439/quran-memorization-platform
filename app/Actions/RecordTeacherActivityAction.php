<?php

namespace App\Actions;

use App\Models\TeacherAttendance;
use Illuminate\Database\QueryException;
use Illuminate\Support\Carbon;

/**
 * حضور Activity-Based (§1، §5 من وثيقة التحقق المعماري): لا GPS ولا
 * تسجيل يدوي من المعلم — يُستدل الحضور من أول نشاط فعلي في اليوم
 * (تسميع أو أي نشاط مستقبلي آخر). Unique constraint
 * (organization_id, teacher_id, mosque_id, date) يمنع تكرار السجل
 * لليوم نفسه في نفس المسجد، فيمكن للمعلم تسجيل حضور مستقل لكل مسجد
 * يدرّس فيه في اليوم ذاته.
 */
class RecordTeacherActivityAction
{
    public function execute(int $organizationId, int $teacherId, int $mosqueId, ?Carbon $at = null): TeacherAttendance
    {
        $at ??= now();
        $date = $at->toDateString();

        $attendance = TeacherAttendance::withoutGlobalScopes()
            ->where('organization_id', $organizationId)
            ->where('teacher_id', $teacherId)
            ->where('mosque_id', $mosqueId)
            ->whereDate('date', $date)
            ->first();

        if (! $attendance) {
            try {
                return TeacherAttendance::withoutGlobalScopes()->create([
                    'organization_id' => $organizationId,
                    'teacher_id' => $teacherId,
                    'mosque_id' => $mosqueId,
                    'date' => $date,
                    'first_activity_at' => $at,
                    'last_activity_at' => $at,
                    'actions_count' => 1,
                ]);
            } catch (QueryException $exception) {
                $sqlState = $exception->errorInfo[0] ?? null;

                if (! in_array($sqlState, ['23505', '23000'], true)) {
                    throw $exception;
                }

                // طلب متزامن أنشأ السجل أولاً (Race Condition، §4 تهديد #15) — أعد المحاولة كتحديث.
                $attendance = TeacherAttendance::withoutGlobalScopes()
                    ->where('organization_id', $organizationId)
                    ->where('teacher_id', $teacherId)
                    ->where('mosque_id', $mosqueId)
                    ->whereDate('date', $date)
                    ->firstOrFail();
            }
        }

        $attendance->last_activity_at = $at;
        $attendance->actions_count = $attendance->actions_count + 1;
        $attendance->save();

        return $attendance;
    }
}
