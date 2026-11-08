<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * حالة الطالب.
 */
enum StudentStatus: string
{
    case Active = 'active';
    case Transferred = 'transferred';
    case Graduated = 'graduated';
    case Withdrawn = 'withdrawn';
}
