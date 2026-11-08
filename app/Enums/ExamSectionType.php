<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * نوع قسم الامتحان.
 */
enum ExamSectionType: string
{
    case Recitation = 'recitation';
    case Tajweed = 'tajweed';
    case Tafseer = 'tafseer';
}
