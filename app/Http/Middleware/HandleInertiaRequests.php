<?php

namespace App\Http\Middleware;

use App\Models\FeatureFlag;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
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
     * يقرأ كل الإعدادات (المصطلحات الديناميكية) الخاصة بالمنظمة الحالية،
     * مخبأة عبر Redis بمفتاح يضم organization_id (انظر §7).
     *
     * @return array<string, string|null>
     */
    private function sharedSettings(?int $organizationId): array
    {
        return Cache::remember(
            "settings:{$organizationId}",
            now()->addMinutes(10),
            fn () => Setting::withoutGlobalScopes()
                ->where('organization_id', $organizationId)
                ->pluck('value', 'key')
                ->all()
        );
    }

    /**
     * يقرأ كل أعلام الميزات المفعّلة للمنظمة الحالية (أو الافتراضية العالمية
     * إن لم تُخصَّص المنظمة قيمة override)، مخبأة بمفتاح مستقل عن Settings (§7).
     *
     * @return array<string, bool>
     */
    private function sharedFeatureFlags(?int $organizationId): array
    {
        return Cache::remember(
            "feature-flags:{$organizationId}:all",
            now()->addMinutes(10),
            function () use ($organizationId) {
                $defaults = FeatureFlag::withoutGlobalScopes()
                    ->whereNull('organization_id')
                    ->pluck('enabled', 'key');

                $overrides = $organizationId
                    ? FeatureFlag::withoutGlobalScopes()
                        ->where('organization_id', $organizationId)
                        ->pluck('enabled', 'key')
                    : collect();

                return $defaults->merge($overrides)->all();
            }
        );
    }
}
