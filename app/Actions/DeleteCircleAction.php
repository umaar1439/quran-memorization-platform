<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Circle;

/**
 * حذف حلقة (Soft Delete). التفويض يحدث في الـ Controller عبر
 * CirclePolicy::delete قبل تنفيذ الحذف.
 */
class DeleteCircleAction
{
    public function execute(Circle $circle): void
    {
        $circle->delete();
    }
}
