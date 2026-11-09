<?php

declare(strict_types=1);

namespace App\Actions;

use App\DTOs\CircleData;
use App\Models\Circle;
use App\Models\User;

/**
 * إنشاء حلقة داخل منظمة المسؤول الحالي. mosque_id/teacher_id سبق التحقق
 * من انتمائهما لنفس المنظمة ومن صلاحية teacher_id (StoreCircleRequest).
 */
class CreateCircleAction
{
    public function execute(CircleData $data, User $actor): Circle
    {
        $organizationId = $actor->organization_id;

        // Fail-Closed: بلا منظمة لا يمكن نسبة الحلقة (كان يعطي 500).
        if ($organizationId === null) {
            abort(403, __('messages.circles.no_organization_context'));
        }

        $circle = new Circle;
        $circle->organization_id = $organizationId;
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
