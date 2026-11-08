<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * حالة هدف الطالب.
 */
enum GoalStatus: string
{
    case Active = 'active';
    case Completed = 'completed';
    case Failed = 'failed';
    case Cancelled = 'cancelled';
}
