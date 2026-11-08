<?php

declare(strict_types=1);

namespace App\Queries;

use App\Enums\RecitationRating;
use App\Enums\RecitationType;
use App\Models\Circle;
use App\Models\Recitation;
use App\Models\StudentProfile;
use Illuminate\Support\Collection;

/**
 * يبني بيانات لوحة تحكم الحلقة (Circle Executive Dashboard).
 *
 * نُقلت من الـ Controller لسببين: (1) إبقاء الـ Controller رقيقاً،
 * (2) القضاء على قراءة غير محدودة لكل تسميعات الحلقة عبر:
 *     - اختيار الأعمدة اللازمة فقط،
 *     - تحميل الأخطاء لم يعد مطلوباً إطلاقاً،
 *     - cursor() لتفريغ الذاكرة على الحلقات ذات التاريخ الطويل.
 */
class CircleDashboardQuery
{
    /**
     * إجمالي آيات المصحف — مرجع حساب نسبة الحفظ.
     */
    public const TOTAL_QURAN_AYAHS = 6236;

    /**
     * @return array<string, mixed>
     */
    public function build(Circle $circle): array
    {
        $circle->load(['mosque', 'teacher']);

        $students = $circle->studentProfiles()
            ->with('user')
            ->orderBy('created_at')
            ->get();

        $byStudent = [];
        $monthRecitationsByStudent = [];
        $startOfMonth = now()->startOfMonth();

        // cursor(): لا نحمّل كل الصفوف في الذاكرة دفعة واحدة.
        $cursor = Recitation::withoutGlobalScopes()
            ->where('circle_id', $circle->id)
            ->orderBy('performed_at')
            ->cursor();

        foreach ($cursor as $recitation) {
            $studentId = $recitation->student_id;
            $byStudent[$studentId][] = $recitation;

            if ($recitation->performed_at !== null && $recitation->performed_at->gte($startOfMonth)) {
                $monthRecitationsByStudent[$studentId] = true;
            }
        }

        $studentsData = $students->map(function (StudentProfile $student) use ($byStudent): array {
            $own = collect($byStudent[$student->id] ?? []);

            $memorized = $this->countDistinctMemorizedAyahs($own);
            $count = $own->count();
            $excellent = $own->filter(
                fn (Recitation $recitation): bool => $recitation->rating === RecitationRating::Excellent
            )->count();
            $accuracy = $count > 0 ? round($excellent / $count * 100, 2) : 0.0;

            return [
                'student' => $student,
                'memorized_ayahs' => $memorized,
                'recitation_count' => $count,
                'accuracy' => $accuracy,
                'last_activity_at' => $own->last()?->performed_at,
                'progress_percentage' => round($memorized / self::TOTAL_QURAN_AYAHS * 100, 2),
            ];
        });

        $totalStudents = $students->count();
        $recitationsThisMonth = $monthRecitationsByStudent === []
            ? 0
            : Recitation::withoutGlobalScopes()
                ->where('circle_id', $circle->id)
                ->where('performed_at', '>=', $startOfMonth)
                ->count();
        $activeStudents = count($monthRecitationsByStudent);
        $attendanceRate = $totalStudents > 0 ? round($activeStudents / $totalStudents * 100, 2) : 0.0;
        $overallProgress = $totalStudents > 0 ? round((float) $studentsData->avg('progress_percentage'), 2) : 0.0;

        $topStudent = $studentsData
            ->sortByDesc(fn (array $data): float => $data['memorized_ayahs'] * 100 + $data['recitation_count'] * 10 + $data['accuracy'])
            ->first();

        return [
            'circle' => $circle,
            'summary' => [
                'total_students' => $totalStudents,
                'recitations_this_month' => $recitationsThisMonth,
                'attendance_rate' => $attendanceRate,
                'overall_progress' => $overallProgress,
            ],
            'students' => $studentsData->values()->all(),
            'topStudent' => $topStudent,
        ];
    }

    /**
     * يحسب عدد الآيات الفريدة المحفوظة (type=new) من مجموعة تسميعات.
     *
     * @param  Collection<int, Recitation>  $recitations
     */
    private function countDistinctMemorizedAyahs(Collection $recitations): int
    {
        $covered = [];

        foreach ($recitations as $recitation) {
            if ($recitation->type !== RecitationType::New) {
                continue;
            }

            for ($ayah = $recitation->ayah_from; $ayah <= $recitation->ayah_to; $ayah++) {
                $covered["{$recitation->surah_number}:{$ayah}"] = true;
            }
        }

        return count($covered);
    }
}
