<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Organization;
use App\Models\Setting;
use App\Support\SettingsCache;
use Illuminate\Database\Seeder;

/**
 * يزرع الإعدادات الافتراضية (المصطلحات الديناميكية) لكل منظمة — idempotent.
 * بدونها تظهر صفحات الإعدادات فارغة ويستخدم النظام قيماً صفرية للمصطلحات.
 */
class SettingSeeder extends Seeder
{
    /**
     * @var list<array{key: string, value: string, group: string}>
     */
    public const DEFAULTS = [
        ['key' => 'circle_label', 'value' => 'حلقة', 'group' => 'terminology'],
        ['key' => 'mosque_label', 'value' => 'مسجد', 'group' => 'terminology'],
        ['key' => 'exam_pass_threshold', 'value' => '60', 'group' => 'exams'],
        ['key' => 'date_format', 'value' => 'Y-m-d', 'group' => 'localization'],
        ['key' => 'default_locale', 'value' => 'ar', 'group' => 'localization'],
    ];

    public function __construct(
        private readonly SettingsCache $cache,
    ) {}

    public function run(): void
    {
        Organization::query()
            ->select(['id'])
            ->chunkById(100, function ($organizations): void {
                foreach ($organizations as $organization) {
                    $this->seedForOrganization((int) $organization->id);
                }
            });
    }

    public function seedForOrganization(int $organizationId): void
    {
        foreach (self::DEFAULTS as $setting) {
            // firstOrCreate: لا نطمس قيمة عدّلها مدير المنظمة.
            Setting::withoutGlobalScopes()->firstOrCreate(
                ['organization_id' => $organizationId, 'key' => $setting['key']],
                ['value' => $setting['value'], 'group' => $setting['group']],
            );
        }

        $this->cache->forget($organizationId);
    }
}
