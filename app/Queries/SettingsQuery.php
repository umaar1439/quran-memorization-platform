<?php

declare(strict_types=1);

namespace App\Queries;

use App\Models\Setting;
use Illuminate\Database\Eloquent\Collection;

/**
 * قراءة إعدادات المنظمة (المصطلحات الديناميكية) لصفحة الإدارة. القراءة
 * اليومية السريعة تمر عبر SettingsCache، أما هذه الصفحة فتحتاج كل الصفوف
 * مرتبة لعرضها وتعديلها.
 */
class SettingsQuery
{
    /**
     * @return Collection<int, Setting>
     */
    public function allForOrganization(?int $organizationId): Collection
    {
        return Setting::withoutGlobalScopes()
            ->where('organization_id', $organizationId)
            ->orderBy('group')
            ->orderBy('key')
            ->get();
    }
}
