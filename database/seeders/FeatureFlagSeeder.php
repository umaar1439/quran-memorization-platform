<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\FeatureFlag;
use App\Models\Organization;
use App\Policies\FeatureFlagPolicy;
use App\Support\FeatureFlagCache;
use Illuminate\Database\Seeder;

/**
 * يزرع أعلام الميزات القابلة للتحكم الذاتي لكل منظمة (مفعّلة افتراضياً).
 *
 * السبب: بدون هذا الـ Seeder تُرجع مسارات الميزات (مثل /certificates) 403
 * لكل الأدوار ما عدا الـ super-admin، لأن Middleware لا يجد أي علم مفعّل.
 * idempotent: يمكن تشغيله أكثر من مرة بأمان.
 */
class FeatureFlagSeeder extends Seeder
{
    /**
     * @var array<string, bool>
     */
    public const DEFAULTS = [
        'certificates.enabled' => true,
        'advanced-reports.enabled' => true,
    ];

    public function __construct(
        private readonly FeatureFlagCache $cache,
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
        $keys = array_values(array_unique([...array_keys(self::DEFAULTS), ...FeatureFlagPolicy::SELF_SERVICE_FLAGS]));

        foreach ($keys as $key) {
            // firstOrCreate لا updateOrCreate: إعادة الزرع يجب ألا تعيد ضبط
            // تفضيلات مدير المنظمة بعد أن غيّرها من صفحة أعلام الميزات.
            FeatureFlag::withoutGlobalScopes()->firstOrCreate(
                ['organization_id' => $organizationId, 'key' => $key],
                ['enabled' => self::DEFAULTS[$key] ?? false],
            );
        }

        $this->cache->forget($organizationId);
    }
}
