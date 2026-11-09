<?php

declare(strict_types=1);

namespace App\Queries;

use App\Models\User;
use App\Support\RoleHierarchy;
use Illuminate\Database\Eloquent\Collection;

/**
 * خيارات المستخدمين حسب الأدوار (مثلاً مسؤولو الحلقات) لقوائم النماذج
 * المنسدلة، بحدّ أقصى صريح يمنع تحميل جدول غير محدود.
 *
 * User لا يستخدم BelongsToOrganization (لا Global Scope)، لذا التقييد
 * بالمنظمة هنا صريح — بدونه تتسرب أسماء معلمي المنظمات الأخرى إلى قائمة
 * الاختيار في صفحة الحلقات (تسريب بيانات عابر للمستأجرين).
 */
class UserOptionsQuery
{
    /**
     * @param  list<string>  $roles  الأدوار المقبولة (معلم/مشرف للحلقات)
     * @return Collection<int, User>
     */
    public function byRoles(array $roles, User $actor): Collection
    {
        return User::query()
            ->role($roles)
            ->when(
                ! $actor->hasRole(RoleHierarchy::SUPER_ADMIN),
                // فاعل بلا منظمة ⇒ where('organization_id', null) ⇒ نتيجة فارغة.
                fn ($query) => $query->where('organization_id', $actor->organization_id),
            )
            ->orderBy('name')
            ->limit((int) config('tahfeez.filter_options_limit'))
            ->get(['id', 'name']);
    }
}
