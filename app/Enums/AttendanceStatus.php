<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * حالة حضور الطالب في يوم واحد.
 *
 * التأخير يُحتسب حضوراً فعلياً في النسب (الطالب حضر ولو متأخراً)، بينما
 * الاستئذان (excused) لا يُحتسب غياباً ولا حضوراً كاملاً — يظهر منفصلاً في
 * الملخص كي لا تُشوَّه نسبة الحضور بسبب إذن مسبق.
 */
enum AttendanceStatus: string
{
    case Present = 'present';
    case Absent = 'absent';
    case Late = 'late';
    case Excused = 'excused';

    /**
     * هل تُحتسب هذه الحالة حضوراً في نسبة الحضور؟
     */
    public function countsAsAttended(): bool
    {
        return $this === self::Present || $this === self::Late;
    }
}
