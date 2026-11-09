<?php

declare(strict_types=1);

namespace App\Actions;

use App\DTOs\MosqueData;
use App\Models\Mosque;
use App\Models\User;

/**
 * إنشاء مسجد داخل منظمة المسؤول الحالي. organization_id لا يُقبل من
 * العميل بل يُسند هنا صراحةً (منع Cross-Tenant Creation).
 *
 * الإسناد صريح حقلاً بحقل بدل `Mosque::create($array)` حتى لا يعتمد السلوك
 * على $fillable وحده (نفس نمط CreateUserAction).
 */
class CreateMosqueAction
{
    public function execute(MosqueData $data, User $actor): Mosque
    {
        $organizationId = $actor->organization_id;

        // Fail-Closed: لا منظمة ⇒ لا مسجد. كان تمرير null إلى عمود NOT NULL
        // يُنتج 500 بدل رفض واضح.
        if ($organizationId === null) {
            abort(403, __('messages.mosques.no_organization_context'));
        }

        $mosque = new Mosque;
        $mosque->organization_id = $organizationId;
        $mosque->name = $data->name;
        $mosque->address = $data->address;
        $mosque->city = $data->city;
        $mosque->phone = $data->phone;
        $mosque->status = $data->status;
        $mosque->save();

        return $mosque;
    }
}
