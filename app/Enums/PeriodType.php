<?php

namespace App\Enums;

/**
 * نوع الفترة الزمنية لهدف الطالب.
 */
enum PeriodType: string
{
    case Daily = 'daily';
    case Weekly = 'weekly';
    case Monthly = 'monthly';
}
