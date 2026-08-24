<?php

namespace App\Rules;

use App\Models\Surah;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * يتحقق أن رقم السورة بين 1-114 وموجود فعلاً في جدول surahs (جدول عالمي
 * مرجعي — انظر §1 من وثيقة التحقق المعماري). لا يعتمد فقط على النطاق
 * العددي لأن الجدول قد لا يكون مُهيّأ بالكامل في بيئة معينة.
 */
class ValidSurahNumber implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_numeric($value) || (int) $value < 1 || (int) $value > 114) {
            $fail('رقم السورة يجب أن يكون بين 1 و 114.');

            return;
        }

        $exists = Surah::query()->where('number', (int) $value)->exists();

        if (! $exists) {
            $fail('رقم السورة المحدد غير موجود في المرجع القرآني.');
        }
    }
}
