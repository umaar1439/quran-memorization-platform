<?php

namespace App\Enums;

/**
 * حالة نتيجة الامتحان (بوابة القفل بعد الاعتماد).
 */
enum ExamResultStatus: string
{
    case Draft = 'draft';
    case Submitted = 'submitted';
    case Approved = 'approved';
}
