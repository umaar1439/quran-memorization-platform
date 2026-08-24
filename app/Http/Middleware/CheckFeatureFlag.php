<?php

namespace App\Http\Middleware;

use App\Models\FeatureFlag;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

/**
 * يتحقق من تفعيل feature flag محدد للمنظمة الحالية قبل السماح بالمرور.
 * يرفض بـ 403 إن كان الـ flag معطلاً. Cache Key يضم organization_id
 * دائماً لمنع التسرب بين المستأجرين (انظر §6.3، §7).
 */
class CheckFeatureFlag
{
    public function handle(Request $request, Closure $next, string $flag): Response
    {
        $user = Auth::user();
        $organizationId = $user?->organization_id;

        // Super Admin عابر للمنظمات — لا يحتاج فحص feature flag منظمة محددة.
        if ($user && method_exists($user, 'hasRole') && $user->hasRole('super-admin')) {
            return $next($request);
        }

        $enabled = Cache::remember(
            "feature-flags:{$organizationId}:{$flag}",
            now()->addMinutes(10),
            function () use ($organizationId, $flag) {
                return FeatureFlag::withoutGlobalScopes()
                    ->where('organization_id', $organizationId)
                    ->where('key', $flag)
                    ->value('enabled') ?? false;
            }
        );

        if (! $enabled) {
            abort(403, 'هذه الميزة غير مفعّلة لمنظمتك.');
        }

        return $next($request);
    }
}
