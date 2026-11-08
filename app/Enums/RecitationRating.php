<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * تقييم التسميع.
 */
enum RecitationRating: string
{
    case Excellent = 'excellent';
    case VeryGood = 'very_good';
    case Good = 'good';
    case NeedsWork = 'needs_work';
}
