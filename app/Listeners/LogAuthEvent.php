<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Actions\CreateAuditLogAction;
use App\Models\User;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Http\Request;

/**
 * يسجّل أحداث المصادقة (Login/Logout/Failed) في audit_logs (Phase 14
 * نقطة 4). audit_logs.organization_id غير nullable — لذا يُسجَّل الحدث
 * فقط إن أمكن ربطه بمنظمة فعلية (مستخدم موجود بمنظمة معروفة)؛ محاولات
 * فاشلة ببريد غير موجود إطلاقاً لا تُسجَّل هنا (لا يوجد tenant تُنسب له،
 * ولا نريد الإفشاء عن وجود/عدم وجود البريد — انظر §4 تهديد #16).
 */
class LogAuthEvent
{
    public function __construct(
        private readonly CreateAuditLogAction $createAuditLog,
        private readonly Request $request,
    ) {}

    public function handleLogin(Login $event): void
    {
        $user = $event->user;

        if (! $user instanceof User || $user->organization_id === null) {
            return;
        }

        $this->createAuditLog->execute(
            organizationId: $user->organization_id,
            event: 'auth.login',
            auditableType: User::class,
            auditableId: $user->id,
            oldValues: null,
            newValues: null,
            user: $user,
            request: $this->request,
        );
    }

    public function handleLogout(Logout $event): void
    {
        $user = $event->user;

        if (! $user instanceof User || $user->organization_id === null) {
            return;
        }

        $this->createAuditLog->execute(
            organizationId: $user->organization_id,
            event: 'auth.logout',
            auditableType: User::class,
            auditableId: $user->id,
            oldValues: null,
            newValues: null,
            user: $user,
            request: $this->request,
        );
    }

    public function handleFailed(Failed $event): void
    {
        $user = $event->user;

        if (! $user instanceof User || $user->organization_id === null) {
            return;
        }

        $this->createAuditLog->execute(
            organizationId: $user->organization_id,
            event: 'auth.failed',
            auditableType: User::class,
            auditableId: $user->id,
            oldValues: null,
            newValues: null,
            user: null,
            request: $this->request,
        );
    }
}
