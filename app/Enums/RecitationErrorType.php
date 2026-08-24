<?php

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
