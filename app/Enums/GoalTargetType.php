<?php

namespace App\Enums;

/**
 * نوع هدف الطالب: حفظ أو مراجعة.
 */
enum GoalTargetType: string
{
    case Memorization = 'memorization';
    case Revision = 'revision';
}
