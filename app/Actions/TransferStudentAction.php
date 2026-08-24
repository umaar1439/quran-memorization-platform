<?php

namespace App\Actions;

use App\Models\Circle;
use App\Models\Mosque;
use App\Models\StudentProfile;
use App\Models\StudentTransfer;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * ينفّذ نقل طالب بين حلقات/مساجد داخل نفس المنظمة (§1، §4 تهديد #15
 * Race Conditions، §5 دورة حياة البيانات — النقل لا يغيّر السجلات
 * التاريخية). كل الخطوات داخل معاملة واحدة: فشل أي خطوة يُبطل الكل.
 */
class TransferStudentAction
{
    public function __construct(
        private readonly CreateAuditLogAction $createAuditLog,
    ) {}

    /**
     * @param  array<string, mixed>  $data  بيانات مُصادَق عليها من TransferStudentRequest::validated()
     */
    public function execute(array $data, User $actor, ?Request $request = null): StudentTransfer
    {
        return DB::transaction(function () use ($data, $actor, $request) {
            // قفل صف الطالب لمنع Race Condition (اعتماد نقلين متزامنين
            // لنفس الطالب لوجهتين مختلفتين — §4 تهديد #15).
            $student = StudentProfile::withoutGlobalScopes()
                ->where('id', $data['student_id'])
                ->lockForUpdate()
                ->first();

            if (! $student) {
                throw ValidationException::withMessages([
                    'student_id' => 'الطالب غير موجود.',
                ]);
            }

            // 1) التحقق من العضوية الحالية (الطالب فعلاً في from_circle المتوقعة).
            if ($actor->organization_id === null || $actor->organization_id !== $student->organization_id) {
                throw ValidationException::withMessages([
                    'student_id' => 'الطالب لا ينتمي لمنظمتك.',
                ]);
            }

            $fromMosqueId = $student->mosque_id;
            $fromCircleId = $student->circle_id;

            // 2) التحقق أن الوجهة (to_mosque, to_circle) في نفس المنظمة.
            $toMosque = Mosque::withoutGlobalScopes()
                ->where('id', $data['to_mosque_id'])
                ->where('organization_id', $student->organization_id)
                ->first();

            if (! $toMosque) {
                throw ValidationException::withMessages([
                    'to_mosque_id' => 'المسجد الهدف لا ينتمي لنفس منظمة الطالب.',
                ]);
            }

            $toCircleId = $data['to_circle_id'] ?? null;

            if ($toCircleId !== null) {
                $toCircle = Circle::withoutGlobalScopes()
                    ->where('id', $toCircleId)
                    ->where('organization_id', $student->organization_id)
                    ->where('mosque_id', $toMosque->id)
                    ->first();

                if (! $toCircle) {
                    throw ValidationException::withMessages([
                        'to_circle_id' => 'الحلقة الهدف لا تنتمي لنفس المنظمة/المسجد.',
                    ]);
                }
            }

            // 4) إنشاء سجل StudentTransfer (السجل التاريخي الوحيد لمعرفة متى ولماذا تغيّر الانتساب).
            $transfer = StudentTransfer::withoutGlobalScopes()->create([
                'organization_id' => $student->organization_id,
                'student_id' => $student->id,
                'from_mosque_id' => $fromMosqueId,
                'to_mosque_id' => $toMosque->id,
                'from_circle_id' => $fromCircleId,
                'to_circle_id' => $toCircleId,
                'transferred_by_user_id' => $actor->id,
                'reason' => $data['reason'] ?? null,
                'metadata' => $data['metadata'] ?? null,
                'transferred_at' => now(),
            ]);

            // 5) تحديث student_profile — السجلات التاريخية (تسميع/حضور) لا تتغيّر.
            $oldValues = [
                'mosque_id' => $student->mosque_id,
                'circle_id' => $student->circle_id,
            ];

            $student->mosque_id = $toMosque->id;
            $student->circle_id = $toCircleId;
            $student->save();

            // 6) سجل AuditLog.
            $this->createAuditLog->execute(
                organizationId: $student->organization_id,
                event: 'student.transferred',
                auditableType: StudentProfile::class,
                auditableId: $student->id,
                oldValues: $oldValues,
                newValues: [
                    'mosque_id' => $student->mosque_id,
                    'circle_id' => $student->circle_id,
                ],
                user: $actor,
                request: $request,
            );

            return $transfer;
        });
    }
}
