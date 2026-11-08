<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Circle;

/**
 * تحديث بيانات حلقة قائمة. organization_id غير قابل للتعديل من هنا؛
 * نطاق الملكية مضمون عبر Global Scope + CirclePolicy::update.
 */
class UpdateCircleAction
{
    /**
     * @param  array<string, mixed>  $data  بيانات مصادَق عليها من StoreCircleRequest::validated()
     */
    public function execute(Circle $circle, array $data): Circle
    {
        $circle->update($data);

        return $circle;
    }
}
