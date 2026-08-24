<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);

        $this->configureRateLimiters();
    }

    /**
     * محددات معدل الطلبات (Rate Limiters) — دفاع عن Brute Force،
     * Credential Stuffing، وإساءة استخدام العمليات الحساسة (§4).
     */
    private function configureRateLimiters(): void
    {
        RateLimiter::for('login', function ($request) {
            $email = (string) $request->input('email');

            return Limit::perMinute(5)->by($email.'|'.$request->ip());
        });

        RateLimiter::for('password-reset', function ($request) {
            $email = (string) $request->input('email');

            // §36.2: خنق لكل حساب وكل IP معاً لمنع استخدام تدفق الاستعادة
            // كوسيلة لكشف الحسابات (Enumeration Oracle) دون حجب المستخدمين
            // الشرعيين على الشبكات المشتركة.
            return Limit::perMinute(3)->by($email.'|'.$request->ip());
        });

        RateLimiter::for('email-verification', function ($request) {
            // §36.3: خنق إعادة إرسال تأكيد البريد لكل حساب (أو IP إن لم يصادق بعد).
            $key = $request->user()?->id ?: $request->ip();

            return Limit::perMinute(6)->by($key);
        });

        RateLimiter::for('report-generation', function ($request) {
            return Limit::perMinute(5)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('certificate-generation', function ($request) {
            return Limit::perMinute(5)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('certificate-verification', function ($request) {
            return Limit::perMinute(10)->by($request->ip());
        });

        RateLimiter::for('sync-api', function ($request) {
            return Limit::perMinute(30)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('bulk-actions', function ($request) {
            return Limit::perMinute(3)->by($request->user()?->id ?: $request->ip());
        });
    }
}
