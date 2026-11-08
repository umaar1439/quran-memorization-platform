<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Mosque;

/**
 * تحديث بيانات مسجد قائم. organization_id غير قابل للتعديل من هنا؛
 * نطاق الملكية مضمون مسبقاً عبر Global Scope + MosquePolicy::update.
 */
class UpdateMosqueAction
{
    /**
     * @param  array<string, mixed>  $data  بيانات مصادَق عليها من StoreMosqueRequest::validated()
     */
    public function execute(Mosque $mosque, array $data): Mosque
    {
        $mosque->update($data);

        return $mosque;
    }
}
