<?php

namespace App\Enums;

/**
 * حالة الامتحان.
 */
enum ExamStatus: string
{
    case Draft = 'draft';
    case Scheduled = 'scheduled';
    case InProgress = 'in_progress';
    case Completed = 'completed';
    case Approved = 'approved';
}
