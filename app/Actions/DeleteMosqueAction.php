<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Mosque;

/**
 * حذف مسجد (Soft Delete). التفويض يحدث في الـ Controller عبر
 * MosquePolicy::delete قبل تنفيذ الحذف.
 */
class DeleteMosqueAction
{
    public function execute(Mosque $mosque): void
    {
        $mosque->delete();
    }
}
