<?php

declare(strict_types=1);

namespace App\Actions;

use App\DTOs\TeacherData;
use App\Models\TeacherProfile;
use Illuminate\Support\Facades\DB;

/**
 * تحديث بيانات معلم: حساب المستخدم + الملف المهني + ربط الحلقات،
 * داخل معاملة واحدة. الحقول المحمية (organization_id) لا تُعدَّل هنا إطلاقاً.
 *
 * لا يستقبل المسؤول الحالي: التفويض يحدث في UpdateTeacherRequest/الـ Policy،
 * والمنظمة تُشتق من ملف المعلم نفسه.
 */
class UpdateTeacherAction
{
    public function __construct(
        private readonly SyncTeacherCirclesAction $syncCircles,
    ) {}

    public function execute(TeacherProfile $teacher, TeacherData $data): TeacherProfile
    {
        // مصدر الحقيقة للمنظمة هو ملف المعلم نفسه، لا المسؤول الحالي: الـ Policy
        // تضمن تطابقهما، واشتقاقه من هنا يمنع تمرير organization_id فارغ
        // (super-admin بلا منظمة) إلى SyncTeacherCirclesAction.
        $organizationId = (int) $teacher->organization_id;

        return DB::transaction(function () use ($teacher, $data, $organizationId): TeacherProfile {
            $user = $teacher->user;
            abort_if($user === null, 404, __('messages.teachers.missing_account'));

            $user->name = (string) $data->name;
            $user->email = (string) $data->email;
            $user->phone = $data->phone;
            $user->status = $data->status;

            if ($data->password !== null && $data->password !== '') {
                $user->password = $data->password;
            }

            $user->save();

            $teacher->update([
                'specialization' => $data->specialization,
                'ijazah_details' => $data->ijazahDetails,
                'hired_at' => $data->hiredAt,
            ]);

            $this->syncCircles->execute($user, $data->circleIds, $organizationId);

            return $teacher;
        });
    }
}
