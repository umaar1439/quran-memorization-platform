<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

/**
 * تطبيع تاريخ سجل الحضور والتحقق منه قبل أي كتابة.
 *
 * التحقق موجود أيضاً في FormRequest، لكنه يُكرَّر هنا لأن الـ Actions تُستدعى
 * من مسارات أخرى (اختبارات، مزامنة دون اتصال، أوامر صيانة) ولا يجوز أن تعتمد
 * على وجود طبقة HTTP أمامها. رفض التاريخ المستقبلي ليس شكلياً: السماح به
 * يفتح باب تزوير نسب الحضور بحصص لم تحدث.
 */
final class AttendanceDate
{
    /**
     * يُرجع التاريخ المطلوب أو تاريخ اليوم، بصيغة Y-m-d، دون قيد المستقبل.
     * يُستخدم للقراءة (عرض ورقة يوم قادم أو سجل قديم).
     *
     * @throws ValidationException
     */
    public static function normalise(?string $date): string
    {
        $resolved = $date ?? now()->toDateString();

        if (! Carbon::hasFormat($resolved, 'Y-m-d')) {
            throw ValidationException::withMessages([
                'date' => __('messages.attendance.invalid_date'),
            ]);
        }

        return $resolved;
    }

    /**
     * يُرجع التاريخ المطلوب أو تاريخ اليوم، بصيغة Y-m-d، ويرفض المستقبل.
     * يُستخدم قبل أي كتابة.
     *
     * @throws ValidationException
     */
    public static function resolve(?string $date): string
    {
        $resolved = self::normalise($date);

        if (Carbon::parse($resolved)->startOfDay()->isAfter(today())) {
            throw ValidationException::withMessages([
                'date' => __('messages.attendance.future_date'),
            ]);
        }

        return $resolved;
    }
}
