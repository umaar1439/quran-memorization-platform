<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Mosque;
use App\Models\User;

/**
 * كل تحقق يبدأ بحد المستأجر (organization_id match) قبل فحص الصلاحية
 * عبر Spatie — انظر §2 حدود الأمان، §3 مصفوفة الصلاحيات.
 */
class MosquePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('circles.view') || $user->can('students.view') || $user->hasRole('super-admin');
    }

    public function view(User $user, Mosque $mosque): bool
    {
        if (! $this->withinTenant($user, $mosque)) {
            return false;
        }

        return $user->can('circles.view') || $user->can('students.view');
    }

    public function create(User $user): bool
    {
        return $user->can('students.manage') || $user->can('circles.manage');
    }

    public function update(User $user, Mosque $mosque): bool
    {
        if (! $this->withinTenant($user, $mosque)) {
            return false;
        }

        return $user->can('circles.manage');
    }

    public function delete(User $user, Mosque $mosque): bool
    {
        if (! $this->withinTenant($user, $mosque)) {
            return false;
        }

        return $user->can('circles.manage');
    }

    /**
     * حد المستأجر: Super Admin يتجاوزه بالتصميم؛ غير ذلك يجب تطابق
     * organization_id تماماً (§6.4).
     */
    private function withinTenant(User $user, Mosque $mosque): bool
    {
        if ($user->hasRole('super-admin')) {
            return true;
        }

        return $user->organization_id !== null
            && $user->organization_id === $mosque->organization_id;
    }
}
