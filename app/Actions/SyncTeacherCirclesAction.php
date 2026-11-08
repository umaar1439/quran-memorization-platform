<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Circle;
use App\Models\User;

/**
 * ربط المعلم بالحلقات المختارة (استخدام متكرر داخل Create/Update).
 * كل الحلقات تُفلتر حصراً على منظمة المسؤول الحالي لمنع التسرب عابر المنظمات.
 */
class SyncTeacherCirclesAction
{
    /**
     * @param  list<int>  $circleIds
     */
    public function execute(User $teacher, array $circleIds, int $organizationId): void
    {
        $validCircleIds = Circle::query()
            ->whereIn('id', $circleIds)
            ->where('organization_id', $organizationId)
            ->pluck('id')
            ->all();

        // إلغاء الربط القديم ثم ربط الحلقات الجديدة.
        Circle::query()->where('teacher_id', $teacher->id)->update(['teacher_id' => null]);
        Circle::query()->whereIn('id', $validCircleIds)->update(['teacher_id' => $teacher->id]);
    }
}
