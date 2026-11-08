<?php

declare(strict_types=1);

namespace App\Actions;

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
    /**
     * @param  array<string, mixed>  $data  بيانات مصادَق عليها من StoreExamRequest::validated()
     */
    public function execute(array $data, User $actor): Exam
    {
        return DB::transaction(function () use ($data, $actor): Exam {
            $exam = Exam::create([
                'mosque_id' => $data['mosque_id'] ?? null,
                'circle_id' => $data['circle_id'] ?? null,
                'title' => $data['title'],
                'exam_type' => $data['exam_type'],
                'scheduled_at' => $data['scheduled_at'] ?? null,
                'status' => ExamStatus::Draft,
                'created_by' => $actor->id,
            ]);

            foreach ($data['sections'] ?? [] as $section) {
                ExamSection::withoutGlobalScopes()->create([
                    'exam_id' => $exam->id,
                    'organization_id' => $exam->organization_id,
                    'surah_number' => $section['surah_number'],
                    'ayah_from' => $section['ayah_from'],
                    'ayah_to' => $section['ayah_to'],
                    'section_type' => $section['section_type'],
                    'max_score' => $section['max_score'],
                ]);
            }

            return $exam;
        });
    }
}
