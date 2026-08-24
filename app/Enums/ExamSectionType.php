<?php

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
