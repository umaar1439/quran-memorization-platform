<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\TeacherProfile;
use App\Models\User;

/**
 * إدارة المعلمين (teachers.*) — محصورة بحد المستأجر (organization_id match)
 * إلا لـ super-admin. تُستخدم في TeacherController لإنشاء/تعديل/حذف المعلمين
 * وربطهم بالحلقات (إلزامي: حلقة واحدة على الأقل).
 */
class TeacherProfilePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('teachers.view');
    }

    public function view(User $user, TeacherProfile $teacher): bool
    {
        if (! $this->withinTenant($user, $teacher)) {
            return false;
        }

        return $user->can('teachers.view');
    }

    /**
     * Fail-Closed: إنشاء معلم يشترط سياق منظمة صريحاً — حتى لـ super-admin —
     * لأن معلمًا بلا organization_id يفلت من كل عزل مستأجرين (حساب «يتيم»).
     * الفحص يتكرر في CreateTeacherAction كحارس ثانٍ.
     */
    public function create(User $user): bool
    {
        return $user->organization_id !== null
            && $user->can('teachers.create');
    }

    public function update(User $user, TeacherProfile $teacher): bool
    {
        if (! $this->withinTenant($user, $teacher)) {
            return false;
        }

        return $user->can('teachers.update');
    }

    public function delete(User $user, TeacherProfile $teacher): bool
    {
        if (! $this->withinTenant($user, $teacher)) {
            return false;
        }

        return $user->can('teachers.delete');
    }

    private function withinTenant(User $user, TeacherProfile $teacher): bool
    {
        if ($user->hasRole('super-admin')) {
            return true;
        }

        return $user->organization_id !== null
            && $user->organization_id === $teacher->organization_id;
    }
}
