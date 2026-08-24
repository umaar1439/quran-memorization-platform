<?php

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
