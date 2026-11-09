<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\User;

/**
 * تراتبية الأدوار — مصدر واحد لقواعد التصعيد (Privilege Escalation) في
 * وحدة المستخدمين، بدل تكرار الشروط داخل الـ Policies والـ Actions.
 *
 * القواعد:
 * - الإسناد (canAssign): لا يُسنَد دور أعلى من دور الفاعل؛ ويجوز إسناد دور
 *   من نفس رتبته (org-admin ينشئ org-admin نظيراً). super-admin رتبته العليا
 *   فلا يُسنَد إلا من super-admin.
 * - الإدارة (canManage): يجب أن تكون رتبة الفاعل أعلى تماماً من رتبة الهدف،
 *   فلا يعدّل مديرٌ نظيراً له (ولا يستولي على كلمة مروره). super-admin يدير
 *   الجميع ما عدا نفسه.
 *
 * الرتب غير المعروفة (0) لا تمنح أي شيء: قاعدة الفشل الآمن.
 *
 * ملاحظة: قراءة أدوار المستخدم تعتمد سياق الفريق الحالي الذي يضبطه
 * ResolveTenant — نفس أساس hasRole()/can() في بقية المشروع.
 */
final class RoleHierarchy
{
    public const SUPER_ADMIN = 'super-admin';

    /**
     * رتبة كل دور؛ الأدوار غير المذكورة = 0.
     *
     * @var array<string, int>
     */
    public const RANKS = [
        'super-admin' => 50,
        'org-admin' => 40,
        'supervisor' => 30,
        'teacher' => 20,
        'student' => 10,
        'parent' => 10,
    ];

    public function rankOf(string $role): int
    {
        return self::RANKS[$role] ?? 0;
    }

    /**
     * @param  iterable<string>  $roles
     */
    public function highestRank(iterable $roles): int
    {
        $highest = 0;

        foreach ($roles as $role) {
            $highest = max($highest, $this->rankOf($role));
        }

        return $highest;
    }

    /**
     * هل يملك الفاعل صلاحية إسناد هذا الدور لمستخدم آخر؟
     */
    public function canAssign(User $actor, string $role): bool
    {
        return $this->canAssignRank($this->highestRank($actor->getRoleNames()->all()), $role);
    }

    public function canAssignRank(int $actorRank, string $role): bool
    {
        return $this->rankOf($role) <= $actorRank;
    }

    /**
     * هل يملك الفاعل صلاحية تعديل/حذف هذا المستخدم؟ (لا يشمل نفسه — إدارة
     * الملف الشخصي مسار آخر: ProfileController).
     */
    public function canManage(User $actor, User $target): bool
    {
        if ($actor->is($target)) {
            return false;
        }

        return $this->canManageRank(
            $this->highestRank($actor->getRoleNames()->all()),
            $this->highestRank($target->getRoleNames()->all()),
        );
    }

    public function canManageRank(int $actorRank, int $targetRank): bool
    {
        if ($actorRank >= self::RANKS[self::SUPER_ADMIN]) {
            return true;
        }

        return $actorRank > $targetRank;
    }
}
