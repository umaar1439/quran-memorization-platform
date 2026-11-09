<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Circle;
use App\Models\Mosque;
use Illuminate\Support\Facades\DB;

/**
 * حذف مسجد (Soft Delete) مع حذف حلقه المرتبطة به — واجهة المستخدم تُخبر
 * المسؤول صراحةً أن الحلقات ستُحذف، وتركها يشير إلى مسجد محذوف يُنتج حلقات
 * معلّقة تظهر بمسجد "—" ولا يمكن إعادة إسنادها لمسجد محذوف نفسه.
 *
 * الحذف المتسلسل يتم نموذجاً بنموذج (لا حذف جماعي عبر الـ Builder) كي يمر
 * كل حذف عبر أحداث Eloquent فيُسجَّل في audit_logs؛ وchunkById يمنع تحميل
 * عدد غير محدود من الحلقات في الذاكرة.
 *
 * التفويض يحدث في الـ Controller عبر MosquePolicy::delete قبل التنفيذ.
 */
class DeleteMosqueAction
{
    /**
     * حجم دفعة الحذف المتسلسل — يوازن بين عدد الاستعلامات والذاكرة.
     */
    private const CASCADE_CHUNK = 100;

    public function execute(Mosque $mosque): void
    {
        DB::transaction(function () use ($mosque): void {
            $mosque->circles()
                ->orderBy('id')
                ->chunkById(self::CASCADE_CHUNK, function ($circles): void {
                    $circles->each(static fn (Circle $circle) => $circle->delete());
                });

            $mosque->delete();
        });
    }
}
