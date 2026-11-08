<?php

declare(strict_types=1);

namespace App\Support;

/**
 * أدوات نصية آمنة لاستعلامات LIKE.
 *
 * نستخدم addcslashes لأن % و _ محارف نمطية (Wildcards) في LIKE: بدون تهريبها
 * يمكن للمستخدم توسيع نطاق البحث (مثلاً "%" تُرجع كل السجلات) أو إبطاء الاستعلام.
 * لا يُكتب أي SQL خام هنا — القيمة تبقى Parameter Binding في Query Builder.
 */
final class SearchTerm
{
    public static function escapeLike(string $value): string
    {
        return addcslashes(trim($value), '\\%_');
    }
}
