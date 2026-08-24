<?php

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
