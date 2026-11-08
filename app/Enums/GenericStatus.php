<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * حالة عامة تُستخدم للمساجد والحلقات (نشط/غير نشط).
 */
enum GenericStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';
}
