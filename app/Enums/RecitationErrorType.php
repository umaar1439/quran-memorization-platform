<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * نوع خطأ التسميع.
 */
enum RecitationErrorType: string
{
    case Memorization = 'memorization';
    case Tajweed = 'tajweed';
    case Pronunciation = 'pronunciation';
    case Tashkeel = 'tashkeel';
}
