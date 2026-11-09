<?php

declare(strict_types=1);

namespace App\Actions;

use App\DTOs\ExamUpdateData;
use App\Models\Exam;

/**
 * تحديث بيانات امتحان. منع التعديل بعد الاعتماد مطبَّق في
 * ExamPolicy::update (طبقة التفويض) قبل الوصول إلى هنا (§5).
 */
class UpdateExamAction
{
    public function execute(Exam $exam, ExamUpdateData $data): Exam
    {
        $attributes = $data->attributes();

        if ($attributes !== []) {
            $exam->update($attributes);
        }

        return $exam;
    }
}
