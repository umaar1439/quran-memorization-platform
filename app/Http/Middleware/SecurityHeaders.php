<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * يضيف رؤوس أمان HTTP قياسية لكل استجابة (انظر §4 نموذج التهديدات:
 * XSS, Session Hijacking, Clickjacking). CSP مضبوط ليتوافق مع
 * Vite dev server و Inertia.js.
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('Content-Security-Policy', $this->contentSecurityPolicy());
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set(
            'Permissions-Policy',
            'geolocation=(), microphone=(), camera=(), payment=()'
        );

        // HSTS يُرسل في الإنتاج فقط: إرساله على http محلي أو نطاق فرعي غير
        // مؤمَّن قد يقفل المتصفح على HTTPS غير متاح (RFC 6797 §7.2).
        if (app()->isProduction()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $response;
    }

    /**
     * CSP متوافق مع Vite في التطوير (HMR يحتاج eval + WebSocket) وصارم في
     * الإنتاج (بلا unsafe-eval). Vite يضيف nonce تلقائياً لسكربتاته عند البناء.
     */
    private function contentSecurityPolicy(): string
    {
        $scriptSrc = app()->isProduction()
            ? "'self' 'unsafe-inline'"
            : "'self' 'unsafe-inline' 'unsafe-eval'";

        $connectSrc = app()->isProduction()
            ? "'self'"
            : "'self' ws: wss:";

        return implode('; ', [
            "default-src 'self'",
            "script-src {$scriptSrc}",
            "style-src 'self' 'unsafe-inline'",
            "img-src 'self' data: blob:",
            "font-src 'self' data:",
            "connect-src {$connectSrc}",
            "frame-ancestors 'none'",
            "base-uri 'self'",
            "form-action 'self'",
        ]);
    }
}
