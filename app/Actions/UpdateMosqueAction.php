<?php

declare(strict_types=1);

namespace App\Actions;

use App\DTOs\MosqueData;
use App\Models\Mosque;

/**
 * تحديث بيانات مسجد قائم. organization_id غير قابل للتعديل من هنا؛
 * نطاق الملكية مضمون مسبقاً عبر Global Scope + MosquePolicy::update.
 */
class UpdateMosqueAction
{
    public function execute(Mosque $mosque, MosqueData $data): Mosque
    {
        $mosque->name = $data->name;
        $mosque->address = $data->address;
        $mosque->city = $data->city;
        $mosque->phone = $data->phone;
        $mosque->status = $data->status;
        $mosque->save();

        return $mosque;
    }
}
