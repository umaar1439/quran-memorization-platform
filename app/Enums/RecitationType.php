<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * نوع التسميع: حفظ جديد / مراجعة قريبة / مراجعة بعيدة.
 */
enum RecitationType: string
{
    case New = 'new';
    case NearRevision = 'near_revision';
    case FarRevision = 'far_revision';
}
