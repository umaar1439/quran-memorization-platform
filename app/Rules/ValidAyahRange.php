<?php

namespace App\Rules;

use App\Models\Surah;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * يتحقق أن ayah_from <= ayah_to وأن كلاهما ضمن حدود السورة الفعلية
 * (surahs.ayah_count). يُطبَّق على حقل ayah_to؛ رقم السورة وبداية النطاق
 * يُمرَّران عبر المُنشئ من الاستدعاء في FormRequest (باستخدام قيم
 * الطلب الحالية) لأن ValidationRule الأساسية لا تصل لبقية بيانات
 * الطلب دون DataAwareRule.
 */
class ValidAyahRange implements ValidationRule
{
    public function __construct(
        private readonly int|string|null $surahNumber = null,
        private readonly int|string|null $ayahFrom = null,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $ayahTo = $value;
        $ayahFrom = $this->ayahFrom;
        $surahNumber = $this->surahNumber;

        if (! is_numeric($ayahFrom) || ! is_numeric($ayahTo)) {
            $fail('نطاق الآيات غير صالح.');

            return;
        }

        $ayahFrom = (int) $ayahFrom;
        $ayahTo = (int) $ayahTo;

        if ($ayahFrom < 1 || $ayahTo < 1) {
            $fail('نطاق الآيات يجب أن يكون أرقاماً موجبة.');

            return;
        }

        if ($ayahFrom > $ayahTo) {
            $fail('يجب أن تكون بداية النطاق (ayah_from) أصغر من أو تساوي نهايته (ayah_to).');

            return;
        }

        if (! is_numeric($surahNumber)) {
            $fail('رقم السورة مطلوب للتحقق من نطاق الآيات.');

            return;
        }

        $surah = Surah::query()->where('number', (int) $surahNumber)->first();

        if (! $surah) {
            $fail('رقم السورة المحدد غير موجود في المرجع القرآني.');

            return;
        }

        if ($ayahTo > $surah->ayah_count) {
            $fail("نطاق الآيات يتجاوز عدد آيات السورة ({$surah->ayah_count}).");
        }
    }
}
