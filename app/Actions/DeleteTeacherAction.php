<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Circle;
use App\Models\TeacherProfile;
use Illuminate\Support\Facades\DB;

/**
 * حذف معلم: إلغاء ربط الحلقات ثم حذف الملف المهني وحساب المستخدم، في معاملة
 * واحدة حتى لا يبقى حساب يتيم بلا ملف ولا حلقة مرتبطة بحساب محذوف.
 */
class DeleteTeacherAction
{
    public function execute(TeacherProfile $teacher): void
    {
        DB::transaction(function () use ($teacher): void {
            $user = $teacher->user;

            if ($user !== null) {
                Circle::query()->where('teacher_id', $user->id)->update(['teacher_id' => null]);
            }

            $teacher->delete();

            $user?->delete();
        });
    }
}
