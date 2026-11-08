<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * نوع هدف الطالب: حفظ أو مراجعة.
 */
enum GoalTargetType: string
{
    case Memorization = 'memorization';
    case Revision = 'revision';
}
