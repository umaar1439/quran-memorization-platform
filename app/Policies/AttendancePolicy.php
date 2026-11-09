<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Circle;
use App\Models\StudentProfile;
use App\Models\User;

/**
 * تخويل وحدة حضور الطلاب.
 *
 * `create` هي البوابة الوحيدة للكتابة: تُستدعى من StoreAttendanceRequest و
 * BulkAttendanceRequest **ومن الـ Actions نفسها** (Defense-in-Depth) بدل
 * تكرار فحوص الصلاحية والمنظمة والملكية في كل Action — مصدر حقيقة واحد
 * يمنع انحراف الفحوص الأمنية بين الطلب والتنفيذ.
 *
 * Fail-Closed: أي فاعل بلا `organization_id` (وغير super-admin) يُرفض، وهو
 * نفس عيب P0 الذي أُصلح في Students/Mosques/Circles (سجلات بمنظمة = 0).
 *
 * لا توجد هنا `view`/`update`/`delete` لأن الوحدة لا تكشف أي مسار يستخدمها؛
 * إضافة سياسات لمسارات غير موجودة تُنتج كوداً ميتاً (G7 في تقرير الاكتشاف).
 */
class AttendancePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('attendance.view') && $this->hasTenantContext($user);
    }

    /**
     * Teacher/Supervisor/Org-Admin يستطيعون التسجيل، والمشرف لا يُقيَّد بحلقة
     * واحدة، أما المعلم فيُقيَّد بحلقاته المسندة إليه رسمياً (IDOR Defense).
     */
    public function create(User $user, ?StudentProfile $student = null): bool
    {
        if (! $user->can('attendance.manage') || ! $this->hasTenantContext($user)) {
            return false;
        }

        if (! $student) {
            return true;
        }

        if (! $this->sameOrganization($user, $student->organization_id)) {
            return false;
        }

        if (self::isCircleBoundTeacher($user)) {
            return $this->ownsStudentCircle($user, $student);
        }

        return true;
    }

    private function hasTenantContext(User $user): bool
    {
        return $user->organization_id !== null || $user->hasRole('super-admin');
    }

    private function sameOrganization(User $user, ?int $organizationId): bool
    {
        if ($user->hasRole('super-admin')) {
            return true;
        }

        return $user->organization_id !== null
            && $organizationId !== null
            && $user->organization_id === $organizationId;
    }

    /**
     * معلم حقيقي (لا يحمل دوراً إدارياً أعلى) — الأدوار الإدارية تُقيَّد
     * بالمنظمة فقط ولا تُقيَّد بحلقة.
     *
     * عامة ومشتركة مع StudentAttendanceQuery: نطاق القراءة (أي حلقات تُعرض)
     * يجب أن يطابق نطاق الكتابة (أي حلقات تُعدَّل)، وإلا عرضت الصفحة حلقات
     * يُرفض حفظ حضورها بـ 403.
     */
    public static function isCircleBoundTeacher(User $user): bool
    {
        return $user->hasRole('teacher')
            && ! $user->hasRole(['super-admin', 'org-admin', 'supervisor']);
    }

    /**
     * يستخدم العلاقة المحمَّلة مسبقاً إن وُجدت (ورقة الحضور تُحمِّل حلقات
     * الطلاب مسبقاً) فلا يتحول تسجيل 200 طالب إلى 200 استعلام.
     */
    private function ownsStudentCircle(User $user, StudentProfile $student): bool
    {
        if ($student->relationLoaded('circle')) {
            return $student->circle?->teacher_id === $user->id;
        }

        return $student->circle_id !== null
            && Circle::query()
                ->whereKey($student->circle_id)
                ->where('teacher_id', $user->id)
                ->exists();
    }
}
