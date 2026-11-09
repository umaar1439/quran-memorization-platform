<?php

declare(strict_types=1);

namespace App\Actions;

use App\DTOs\CircleData;
use App\Models\Circle;

/**
 * تحديث بيانات حلقة قائمة. organization_id غير قابل للتعديل من هنا؛
 * نطاق الملكية مضمون عبر Global Scope + CirclePolicy::update.
 */
class UpdateCircleAction
{
    public function execute(Circle $circle, CircleData $data): Circle
    {
        $circle->mosque_id = $data->mosqueId;
        $circle->teacher_id = $data->teacherId;
        $circle->name = $data->name;
        $circle->level = $data->level;
        $circle->capacity = $data->capacity;
        $circle->status = $data->status;
        $circle->save();

        return $circle;
    }
}
