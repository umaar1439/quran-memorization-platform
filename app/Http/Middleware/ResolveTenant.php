<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\PermissionRegistrar;
use Symfony\Component\HttpFoundation\Response;

/**
 * يحدد المستأجر الحالي (organization_id) من المستخدم المصادَق عليه،
 * يُعيّن Spatie Permission Team Context، ويخزّن organization_id
 * في context مشترك يستخدمه Global Scope (BelongsToOrganization).
 *
 * Super Admin (organization_id = null): يسمح بالمرور بدون تقييد tenant scope
 * (استثناء وحيد ومُدقَّق — انظر §6.4 من وثيقة التحقق المعماري).
 */
class ResolveTenant
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if ($user) {
            $organizationId = $user->organization_id;

            // يُعيّن Spatie Permission team context لضمان أن كل فحص
            // hasRole/can يكون مقيَّداً ضمنياً بمنظمة المستخدم الحالية.
            app(PermissionRegistrar::class)->setPermissionsTeamId($organizationId);

            // يُخزَّن في context مشترك ليستخدمه أي كود لا يريد الاعتماد
            // على Auth::user() مباشرة (مثل Global Scope أو الخدمات).
            app()->instance('current_organization_id', $organizationId);
        } else {
            app()->instance('current_organization_id', null);
        }

        return $next($request);
    }
}
