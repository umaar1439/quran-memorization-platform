<?php

declare(strict_types=1);

namespace App\Providers;

use App\Listeners\AuditModelChanges;
use App\Listeners\LogAuthEvent;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;

/**
 * يسجّل AuditModelChanges على أحداث Eloquent للـ Models المطلوب تدقيقها
 * (Phase 14 نقطة 3)، وأحداث المصادقة (Login/Logout/Failed) للتدقيق
 * (Phase 14 نقطة 4).
 */
class EventServiceProvider extends ServiceProvider
{
    /**
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        Login::class => [
            LogAuthEvent::class.'@handleLogin',
        ],
        Logout::class => [
            LogAuthEvent::class.'@handleLogout',
        ],
        Failed::class => [
            LogAuthEvent::class.'@handleFailed',
        ],
    ];

    public function boot(): void
    {
        // التسجيل مشتق من خريطة المستمع نفسها: أي Model جديد يُضاف هناك
        // يصبح مُدقَّقاً تلقائياً بلا خطوة تسجيل إضافية.
        foreach (array_keys(AuditModelChanges::AUDITED_EVENTS) as $model) {
            Event::listen("eloquent.created: {$model}", [AuditModelChanges::class, 'handleCreated']);
            Event::listen("eloquent.updated: {$model}", [AuditModelChanges::class, 'handleUpdated']);
            Event::listen("eloquent.deleted: {$model}", [AuditModelChanges::class, 'handleDeleted']);
        }
    }
}
