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
 * إنشاء مستخدم إداري: organization_id يُسند من سياق المسؤول الحالي لا من
 * الطلب (منع Privilege Escalation)، والدور يُسند عبر Spatie بعد التحقق من
 * رتبته مقابل رتبة المسؤول.
 *
 * الحقلان organization_id و status ليسا في User::$fillable ولا يمكن تمريرهما
 * إلى create() — كانا يُمرَّران سابقاً فيُرفض الطلب في بيئة strict
 * (MassAssignmentException). هنا يُعيَّنان صريحاً على النموذج.
 *
 * كل الخطوات (User + الدور + سجل التدقيق) داخل معاملة واحدة: لا مستخدم
 * بلا دور، ولا دور بلا أثر تدقيق.
 */
class CreateUserAction
{
    public function __construct(
        private readonly RoleHierarchy $hierarchy,
        private readonly CreateAuditLogAction $createAuditLog,
    ) {}

    public function execute(UserData $data, User $actor, ?Request $request = null): User
    {
        $organizationId = $actor->organization_id;

        // لا سياق منظمة = لا يمكن تحديد مالك المستخدم الجديد؛ الرفض أوضح من
        // إنشاء حساب بلا منظمة (يفلت من حدود المستأجر).
        if ($organizationId === null) {
            abort(403, __('messages.users.no_organization_context'));
        }

        if (! $this->hierarchy->canAssign($actor, $data->role)) {
            throw ValidationException::withMessages([
                'role' => __('messages.users.role_escalation_denied', ['role' => $data->role]),
            ]);
        }

        return DB::transaction(function () use ($data, $actor, $organizationId, $request): User {
            $user = new User;
            $user->fill($data->attributes());
            $user->organization_id = $organizationId;
            $user->status = $data->status;
            $user->password = (string) $data->password;
            $user->save();

            $user->assignRole($data->role);

            // الدور ليس عموداً على users، فلا يسجّله AuditModelChanges
            // (user.created) — نسجّل الإسناد صراحةً لتغطية كاملة.
            $this->createAuditLog->execute(
                organizationId: $organizationId,
                event: 'user.roles_synced',
                auditableType: User::class,
                auditableId: $user->id,
                oldValues: ['roles' => []],
                newValues: ['roles' => [$data->role]],
                user: $actor,
                request: $request,
            );

            return $user;
        });
    }
}
