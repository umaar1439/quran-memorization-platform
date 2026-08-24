<?php

namespace App\Models\Concerns;

use App\Models\Organization;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/**
 * يفرض عزل المستأجرين (Tenant Isolation) عبر Global Scope إلزامي:
 * كل استعلام على Model يستخدم هذا الـ Trait يُقيَّد تلقائياً بـ
 * organization_id الخاص بالمستخدم المصادَق عليه حالياً.
 *
 * الاستثناء الوحيد المسموح به: Super Admin (دور عابر للمنظمات بالتصميم) —
 * انظر §6.4 من وثيقة التحقق المعماري.
 *
 * الجداول العالمية (surahs, ayahs) لا تستخدم هذا الـ Trait إطلاقاً.
 */
trait BelongsToOrganization
{
    /**
     * تفعيل الـ Global Scope وربط organization_id تلقائياً عند الإنشاء.
     */
    protected static function bootBelongsToOrganization(): void
    {
        static::addGlobalScope('organization', function (Builder $builder) {
            $user = Auth::user();

            if (! $user) {
                return;
            }

            // Super Admin يتجاوز حدود المستأجر بالتصميم (استثناء وحيد ومُدقَّق).
            if (method_exists($user, 'hasRole') && $user->hasRole('super-admin')) {
                return;
            }

            if ($user->organization_id) {
                $builder->where($builder->getModel()->getTable().'.organization_id', $user->organization_id);
            }
        });

        static::creating(function (Model $model) {
            if (! $model->organization_id) {
                $user = Auth::user();

                if ($user && $user->organization_id) {
                    $model->organization_id = $user->organization_id;
                }
            }
        });
    }

    /**
     * علاقة المنظمة المالكة لهذا السجل.
     */
    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * استعلام يتجاوز الـ Global Scope عمداً (Super Admin Impersonation فقط).
     */
    public static function withoutOrganizationScope(): Builder
    {
        return static::withoutGlobalScope('organization');
    }
}
