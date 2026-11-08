<?php

use App\Http\Middleware\CheckFeatureFlag;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\ResolveTenant;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\SetRequestId;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Spatie\Permission\Middleware\RoleMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(prepend: [
            SetRequestId::class,
        ]);

        $middleware->web(append: [
            ResolveTenant::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
            SecurityHeaders::class,
        ]);

        $middleware->alias([
            'feature' => CheckFeatureFlag::class,
            'role' => RoleMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // إضافة request_id إلى سياق كل استثناء مُسجَّل (§39 Request Correlation) —
        // يربط أي خطأ بالطلب الأصلي عبر X-Request-ID (SetRequestId middleware).
        $exceptions->context(function () {
            return [
                'request_id' => app()->bound('request_id') ? app('request_id') : null,
            ];
        });
    })->create();
