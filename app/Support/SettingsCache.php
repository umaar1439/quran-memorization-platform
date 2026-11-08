<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

/**
 * مصدر وحيد لقراءة/إبطال ذاكرة إعدادات المنظمة (المصطلحات الديناميكية).
 * المفتاح يضم organization_id دائماً لمنع تسرب الإعدادات بين المستأجرين (§7).
 */
final class SettingsCache
{
    private const TTL_MINUTES = 10;

    /**
     * @return array<string, string|null>
     */
    public function all(?int $organizationId): array
    {
        return Cache::remember(
            $this->key($organizationId),
            now()->addMinutes(self::TTL_MINUTES),
            fn (): array => Setting::withoutGlobalScopes()
                ->where('organization_id', $organizationId)
                ->pluck('value', 'key')
                ->all(),
        );
    }

    public function forget(?int $organizationId): void
    {
        Cache::forget($this->key($organizationId));
    }

    private function key(?int $organizationId): string
    {
        return "settings:{$organizationId}";
    }
}
