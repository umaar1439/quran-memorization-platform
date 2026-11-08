<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * يُولّد UUID فريداً لكل request ويُخزّنه في app container (app('request_id'))
 * ليستخدمه CreateAuditLogAction تلقائياً لربط كل التغييرات الناتجة عن طلب
 * HTTP واحد ببعضها (Traceability كامل — §1 من وثيقة التحقق المعماري).
 * يُضيفه أيضاً كـ header في الاستجابة (X-Request-ID) لتسهيل التتبّع من العميل.
 */
class SetRequestId
{
    public function handle(Request $request, Closure $next): Response
    {
        $requestId = (string) Str::uuid();

        app()->instance('request_id', $requestId);

        $response = $next($request);

        $response->headers->set('X-Request-ID', $requestId);

        return $response;
    }
}
