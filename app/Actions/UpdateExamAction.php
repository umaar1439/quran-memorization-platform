<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Exam;

/**
 * تحديث بيانات امتحان. منع التعديل بعد الاعتماد مطبَّق في
 * ExamPolicy::update (طبقة التفويض) قبل الوصول إلى هنا (§5).
 */
class UpdateExamAction
{
    /**
     * @param  array<string, mixed>  $data  بيانات مصادَق عليها من UpdateExamRequest::validated()
     */
    public function execute(Exam $exam, array $data): Exam
    {
        $exam->update($data);

        return $exam;
    }
}
