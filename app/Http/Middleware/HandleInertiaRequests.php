<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Support\FeatureFlagCache;
use App\Support\SettingsCache;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    public function __construct(
        private readonly FeatureFlagCache $featureFlags,
        private readonly SettingsCache $settings,
    ) {}

    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();
        $organizationId = $user?->organization_id;

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user,
                'roles' => $user && method_exists($user, 'getRoleNames')
                    ? $user->getRoleNames()->values()->all()
                    : [],
                'permissions' => $user && method_exists($user, 'getAllPermissions')
                    ? $user->getAllPermissions()->pluck('name')->values()->all()
                    : [],
            ],
            'settings' => $user ? $this->sharedSettings($organizationId) : [],
            'feature_flags' => $user ? $this->sharedFeatureFlags($organizationId) : [],
            'flash' => [
                'success' => $request->session()->get('success'),
                'error' => $request->session()->get('error'),
            ],
        ];
    }

    /**
     * يقرأ كل الإعدادات (المصطلحات الديناميكية) الخاصة بالمنظمة الحالية
     * عبر SettingsCache (مفتاح يضم organization_id — انظر §7).
     *
     * @return array<string, string|null>
     */
    private function sharedSettings(?int $organizationId): array
    {
        return $this->settings->all($organizationId);
    }

    /**
     * يقرأ كل أعلام الميزات المفعّلة للمنظمة الحالية (أو الافتراضية العالمية
     * إن لم تُخصَّص المنظمة قيمة override) عبر FeatureFlagCache (§7).
     *
     * @return array<string, bool>
     */
    private function sharedFeatureFlags(?int $organizationId): array
    {
        return $this->featureFlags->all($organizationId);
    }
}
