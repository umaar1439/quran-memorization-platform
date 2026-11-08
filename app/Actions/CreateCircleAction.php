<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Circle;
use App\Models\User;

/**
 * إنشاء حلقة داخل منظمة المسؤول الحالي. mosque_id/teacher_id سبق التحقق
 * من انتمائهما لنفس المنظمة في StoreCircleRequest (IDOR Defense).
 */
class CreateCircleAction
{
    /**
     * @param  array<string, mixed>  $data  بيانات مصادَق عليها من StoreCircleRequest::validated()
     */
    public function execute(array $data, User $admin): Circle
    {
        $data['organization_id'] = $admin->organization_id;

        return Circle::create($data);
    }
}
