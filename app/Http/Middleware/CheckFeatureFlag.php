<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Support\FeatureFlagCache;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * يتحقق من تفعيل feature flag محدد للمنظمة الحالية قبل السماح بالمرور.
 * يرفض بـ 403 إن كان الـ flag معطلاً. الذاكرة مفصولة لكل منظمة
 * (FeatureFlagCache) لمنع التسرب بين المستأجرين (انظر §6.3، §7).
 */
class CheckFeatureFlag
{
    public function __construct(
        private readonly FeatureFlagCache $flags,
    ) {}

    public function handle(Request $request, Closure $next, string $flag): Response
    {
        $user = Auth::user();
        $organizationId = $user?->organization_id;

        // Super Admin عابر للمنظمات — لا يحتاج فحص feature flag منظمة محددة.
        if ($user && method_exists($user, 'hasRole') && $user->hasRole('super-admin')) {
            return $next($request);
        }

        if (! $this->flags->enabled($organizationId, $flag)) {
            abort(403, __('messages.features.disabled'));
        }

        return $next($request);
    }
}
