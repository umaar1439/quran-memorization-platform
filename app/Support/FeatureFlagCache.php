<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\FeatureFlag;
use Illuminate\Support\Facades\Cache;

/**
 * مصدر وحيد لقراءة/إبطال ذاكرة أعلام الميزات.
 *
 * المشكلة التي يحلّها: المفاتيح كانت تُبنى يدوياً في ثلاثة أماكن فيصير الإبطال
 * ناقصاً (مثلاً مفتاح middleware لكل علم لا يُبطَل عند تبديل العلم فيبقى
 * التغيير غير مرئي حتى 10 دقائق). هنا كل المفاتيح تُبنى وتُبطَل في مكان واحد.
 *
 * القيمة الفعّالة = تجاوز المنظمة إن وُجد، وإلا العلم العالمي (organization_id = null)،
 * وإلا معطّل — نفس دلالة HandleInertiaRequests (§7).
 */
final class FeatureFlagCache
{
    private const TTL_MINUTES = 10;

    /**
     * @return array<string, bool>
     */
    public function all(?int $organizationId): array
    {
        return Cache::remember(
            $this->allKey($organizationId),
            now()->addMinutes(self::TTL_MINUTES),
            function () use ($organizationId): array {
                $defaults = FeatureFlag::withoutGlobalScopes()
                    ->whereNull('organization_id')
                    ->pluck('enabled', 'key');

                $overrides = $organizationId
                    ? FeatureFlag::withoutGlobalScopes()
                        ->where('organization_id', $organizationId)
                        ->pluck('enabled', 'key')
                    : collect();

                return $defaults->merge($overrides)->map(
                    static fn (mixed $enabled): bool => (bool) $enabled,
                )->all();
            },
        );
    }

    public function enabled(?int $organizationId, string $key): bool
    {
        return (bool) ($this->all($organizationId)[$key] ?? false);
    }

    /**
     * إبطال ذاكرة المنظمة (بما فيها العلم العالمي المشترك بين كل المنظمات).
     */
    public function forget(?int $organizationId): void
    {
        Cache::forget($this->allKey($organizationId));
    }

    private function allKey(?int $organizationId): string
    {
        return "feature-flags:{$organizationId}:all";
    }
}
