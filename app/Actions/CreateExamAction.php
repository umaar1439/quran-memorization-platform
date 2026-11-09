<?php

declare(strict_types=1);

namespace App\Actions;

use App\DTOs\ExamSubmissionData;
use App\Enums\ExamStatus;
use App\Models\Exam;
use App\Models\ExamSection;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * إنشاء امتحان جديد (يبدأ draft دوماً) مع أقسامه الاختيارية في معاملة
 * واحدة: إما يُحفظ الامتحان مع كل أقسامه أو لا شيء (§5 دورة حياة البيانات).
 */
class CreateExamAction
{
    public function execute(ExamSubmissionData $data, User $actor): Exam
    {
        return DB::transaction(function () use ($data, $actor): Exam {
            $exam = Exam::create([
                'mosque_id' => $data->mosqueId,
                'circle_id' => $data->circleId,
                'title' => $data->title,
                'exam_type' => $data->examType,
                'scheduled_at' => $data->scheduledAt,
                'status' => ExamStatus::Draft,
                'created_by' => $actor->id,
            ]);

            foreach ($data->sections as $section) {
                ExamSection::withoutGlobalScopes()->create([
                    'exam_id' => $exam->id,
                    'organization_id' => $exam->organization_id,
                    'surah_number' => $section->surahNumber,
                    'ayah_from' => $section->ayahFrom,
                    'ayah_to' => $section->ayahTo,
                    'section_type' => $section->sectionType,
                    'max_score' => $section->maxScore,
                ]);
            }

            return $exam;
        });
    }
}
