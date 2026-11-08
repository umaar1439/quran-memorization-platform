<?php

declare(strict_types=1);

namespace App\Actions;

use App\DTOs\TeacherData;
use App\Models\TeacherProfile;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * إنشاء معلم: حساب User بدور teacher + TeacherProfile + ربط إلزامي بحلقة
 * واحدة على الأقل، في معاملة واحدة (كل الخطوات أو لا شيء).
 *
 * ملاحظة أمنية مهمة: `organization_id` و`status` غير قابلين للتعبئة الجماعية
 * في User (منع Privilege Escalation) — لذلك يُسندان صراحةً هنا، لا عبر create().
 */
class CreateTeacherAction
{
    public function __construct(
        private readonly SyncTeacherCirclesAction $syncCircles,
    ) {}

    public function execute(TeacherData $data, User $admin): TeacherProfile
    {
        return DB::transaction(function () use ($data, $admin): TeacherProfile {
            $organizationId = (int) $admin->organization_id;

            $user = $data->userId !== null
                ? $this->linkExistingUser($data, $organizationId)
                : $this->createUser($data, $organizationId);

            $user->assignRole('teacher');

            $profile = TeacherProfile::query()->updateOrCreate(
                ['user_id' => $user->id],
                [
                    'organization_id' => $organizationId,
                    'specialization' => $data->specialization,
                    'ijazah_details' => $data->ijazahDetails,
                    'hired_at' => $data->hiredAt,
                ],
            );

            $this->syncCircles->execute($user, $data->circleIds, $organizationId);

            return $profile;
        });
    }

    /**
     * إنشاء حساب جديد مع إسناد صريح للحقول المحمية من التعبئة الجماعية.
     */
    private function createUser(TeacherData $data, int $organizationId): User
    {
        $user = new User;
        $user->name = (string) $data->name;
        $user->email = (string) $data->email;
        $user->password = (string) $data->password;
        $user->phone = $data->phone;
        $user->status = $data->status;
        $user->organization_id = $organizationId;
        $user->save();

        return $user;
    }

    /**
     * ربط حساب موجود داخل نفس المنظمة فقط (منع ربط حساب من منظمة أخرى).
     */
    private function linkExistingUser(TeacherData $data, int $organizationId): User
    {
        $user = User::query()->findOrFail($data->userId);

        abort_if($user->organization_id !== $organizationId, 403, __('messages.teachers.cross_organization_link'));

        $user->status = $data->status;
        $user->phone = $data->phone ?? $user->phone;
        $user->save();

        return $user;
    }
}
