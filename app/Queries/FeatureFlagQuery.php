<?php

declare(strict_types=1);

namespace App\Queries;

use App\Models\FeatureFlag;
use App\Models\User;
use App\Policies\FeatureFlagPolicy;
use Illuminate\Database\Eloquent\Collection;

/**
 * أعلام الميزات المتاحة للمستخدم: Super Admin يرى الكل، ومدير المنظمة يرى
 * فقط أعلام self-service الخاصة بمنظمته. العدد محدود بطبيعته لكن نضع حداً
 * صريحاً احتراماً لقاعدة عدم تحميل جداول بلا سقف.
 *
 * لا نعرض الأعلام العالمية (organization_id = null) لمدير المنظمة لأن
 * FeatureFlagPolicy::manage يشترط تطابق organization_id؛ عرضها كان يعني زر
 * تبديل ينتهي دائماً بـ 403.
 */
class FeatureFlagQuery
{
    /**
     * @return Collection<int, FeatureFlag>
     */
    public function forUser(User $user): Collection
    {
        $query = FeatureFlag::withoutGlobalScopes();

        if (! $user->hasRole('super-admin')) {
            if ($user->organization_id === null) {
                return new Collection;
            }

            $query->where('organization_id', $user->organization_id)
                ->whereIn('key', FeatureFlagPolicy::SELF_SERVICE_FLAGS);
        }

        return $query
            ->orderBy('key')
            ->limit((int) config('tahfeez.filter_options_limit'))
            ->get();
    }
}
