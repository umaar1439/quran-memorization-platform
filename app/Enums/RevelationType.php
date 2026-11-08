<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * نوع نزول السورة: مكية أو مدنية.
 */
enum RevelationType: string
{
    case Meccan = 'meccan';
    case Medinan = 'medinan';
}
