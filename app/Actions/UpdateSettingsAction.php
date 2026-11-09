<?php

declare(strict_types=1);

namespace App\Actions;

use App\DTOs\SettingEntryData;
use App\Models\Setting;
use App\Support\SettingsCache;
use Illuminate\Support\Facades\DB;

/**
 * حفظ إعدادات المنظمة دفعة واحدة (Bulk upsert) داخل معاملة، ثم إبطال الذاكرة.
 * الكتابة الجماعية تمنع N عمليات كتابة منفصلة لمجموعة إعدادات واحدة.
 */
class UpdateSettingsAction
{
    public function __construct(
        private readonly SettingsCache $cache,
    ) {}

    /**
     * @param  list<SettingEntryData>  $settings
     */
    public function execute(int $organizationId, array $settings): void
    {
        $now = now();

        $rows = array_map(static fn (SettingEntryData $item): array => [
            'organization_id' => $organizationId,
            'key' => $item->key,
            'value' => $item->value,
            'group' => $item->group,
            'created_at' => $now,
            'updated_at' => $now,
        ], $settings);

        DB::transaction(function () use ($rows): void {
            Setting::withoutGlobalScopes()->upsert(
                $rows,
                ['organization_id', 'key'],
                ['value', 'group', 'updated_at'],
            );
        });

        $this->cache->forget($organizationId);
    }
}
