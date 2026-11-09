<?php

declare(strict_types=1);

namespace App\Actions;

use App\DTOs\UserData;
use App\Models\User;
use App\Support\RoleHierarchy;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * تحديث مستخدم إداري: الحقول الأساسية + كلمة المرور إن أُرسلت + مزامنة
 * الدور. الحقول المحمية (organization_id) لا تُعدَّل هنا إطلاقاً.
 *
 * التدقيق: AuditModelChanges يسجّل user.updated تلقائياً، لكنه يحذف كلمة
 * المرور من old/new values ولا يرى جدول الأدوار — لذا نسجّل داخل المعاملة
 * الحدثين اللذين لا يغطيهما: user.roles_synced (عند تغيّر فعلي فقط)
 * وuser.password_changed (بلا قيمة كلمة المرور). لا ازدواج للسجلات.
 */
class UpdateUserAction
{
    public function __construct(
        private readonly RoleHierarchy $hierarchy,
        private readonly CreateAuditLogAction $createAuditLog,
    ) {}

    public function execute(User $user, UserData $data, User $actor, ?Request $request = null): User
    {
        if (! $this->hierarchy->canAssign($actor, $data->role)) {
            throw ValidationException::withMessages([
                'role' => __('messages.users.role_escalation_denied', ['role' => $data->role]),
            ]);
        }

        return DB::transaction(function () use ($user, $data, $actor, $request): User {
            $oldRoles = $user->getRoleNames()->values()->all();
            $passwordChanged = $data->hasPassword();

            $user->fill($data->attributes());
            $user->status = $data->status;

            if ($passwordChanged) {
                $user->password = (string) $data->password;
            }

            $user->save();

            if ($oldRoles !== [$data->role]) {
                $user->syncRoles([$data->role]);

                $this->createAuditLog->execute(
                    organizationId: (int) $user->organization_id,
                    event: 'user.roles_synced',
                    auditableType: User::class,
                    auditableId: $user->id,
                    oldValues: ['roles' => $oldRoles],
                    newValues: ['roles' => [$data->role]],
                    user: $actor,
                    request: $request,
                );
            }

            if ($passwordChanged) {
                $this->createAuditLog->execute(
                    organizationId: (int) $user->organization_id,
                    event: 'user.password_changed',
                    auditableType: User::class,
                    auditableId: $user->id,
                    oldValues: null,
                    // القيمة لا تُسجَّل أبداً؛ CreateAuditLogAction يستبدلها
                    // بـ [REDACTED] أيضاً كطبقة حماية ثانية.
                    newValues: ['password' => '[REDACTED]'],
                    user: $actor,
                    request: $request,
                );
            }

            return $user;
        });
    }
}
