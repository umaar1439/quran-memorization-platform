<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Mosque;
use App\Models\User;

/**
 * إنشاء مسجد داخل منظمة المسؤول الحالي. organization_id لا يُقبل من
 * العميل بل يُسند هنا صراحةً (منع Cross-Tenant Creation).
 */
class CreateMosqueAction
{
    /**
     * @param  array<string, mixed>  $data  بيانات مصادَق عليها من StoreMosqueRequest::validated()
     */
    public function execute(array $data, User $admin): Mosque
    {
        $data['organization_id'] = $admin->organization_id;

        return Mosque::create($data);
    }
}
