<?php

namespace App\Actions;

use App\Enums\RecitationType;
use App\Models\Recitation;
use App\Models\StudentProfile;
use App\Models\Surah;
use App\Notifications\ExamRecommendedNotification;

/**
 * يتحقق عند اكتمال طالب لسورة (تغطية كل آياتها بتسميع type=new) من
 * بيانات recitations الفعلية، ويُرسل توصية بامتحان (Notification) —
 * لا إنشاء تلقائي لامتحان (Phase 9 §3). لا يُنشئ Exam أبداً.
 */
class RecommendExamOnCompletionAction
{
    public function execute(StudentProfile $student, int $surahNumber): void
    {
        $surah = Surah::query()->where('number', $surahNumber)->first();

        if (! $surah) {
            return;
        }

        $coveredAyahs = [];

        $recitations = Recitation::withoutGlobalScopes()
            ->where('student_id', $student->id)
            ->where('surah_number', $surahNumber)
            ->where('type', RecitationType::New)
            ->get();

        foreach ($recitations as $recitation) {
            for ($ayah = $recitation->ayah_from; $ayah <= $recitation->ayah_to; $ayah++) {
                $coveredAyahs[$ayah] = true;
            }
        }

        if (count($coveredAyahs) < $surah->ayah_count) {
            return;
        }

        $recipient = $student->circle?->teacher;

        if (! $recipient) {
            return;
        }

        $recipient->notify(new ExamRecommendedNotification(
            studentId: $student->id,
            reason: "الطالب أكمل حفظ سورة {$surah->name_arabic} بالكامل — يُنصح بجدولة امتحان.",
        ));
    }
}
