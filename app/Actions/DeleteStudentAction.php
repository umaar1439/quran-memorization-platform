<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * حذف طالب (Soft Delete): الملف الدراسي وحساب المستخدم في معاملة واحدة.
 *
 * السجلات التاريخية (التسميع، الأهداف، نتائج الاختبارات، الشهادات) لا تُحذف
 * ولا تُعدَّل — إبقاء السجل التعليمي مطلب تدقيقي، و`student_id` يبقى قائماً
 * لأن الحذف ناعم.
 *
 * التفويض يحدث في الـ Controller عبر StudentProfilePolicy::delete، لكن الـ
 * Action يتحقق من النطاق مرة أخرى (Fail-Closed) لأنه قد يُستدعى من مسار
 * غير HTTP (Command/Job) بلا تفويض مسبق.
 */
class DeleteStudentAction
{
    public function execute(StudentProfile $student, User $actor): void
    {
        abort_unless(
            $actor->organization_id !== null && $actor->organization_id === $student->organization_id,
            403,
            __('messages.common.unauthorized'),
        );

        DB::transaction(function () use ($student): void {
            $user = $student->user;

            $student->delete();

            $user?->delete();
        });
    }
}
