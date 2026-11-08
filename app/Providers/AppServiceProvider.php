<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
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
        $this->configureEloquentStrictness();
        $this->configureRateLimiters();

        Vite::prefetch(concurrency: 3);
    }

    /**
     * صرامة Eloquent (§Database Access):
     *  - منع lazy loading (كشف N+1 مبكراً).
     *  - منع إسناد خصائص غير موجودة بشكل صامت.
     *  - منع قراءة خصائص غير موجودة/غير محمَّلة.
     *  - منع الأوامر المدمِّرة لقاعدة البيانات في بيئة الإنتاج.
     *
     * تُفعَّل خارج الإنتاج فقط حتى لا تُسقِط بيئة الإنتاج بسبب استعلام بطيء.
     */
    private function configureEloquentStrictness(): void
    {
        Model::shouldBeStrict(! $this->app->isProduction());

        DB::prohibitDestructiveCommands($this->app->isProduction());
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

        // تأكيد كلمة المرور قبل العمليات الحساسة — يمنع تخمين كلمة المرور
        // من داخل جلسة مسروقة (§4 Session Hijacking).
        RateLimiter::for('password-confirm', function ($request) {
            return Limit::perMinute(6)->by($request->user()?->id ?: $request->ip());
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
