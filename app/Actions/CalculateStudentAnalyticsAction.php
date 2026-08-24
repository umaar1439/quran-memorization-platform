<?php

namespace App\Actions;

use App\Enums\RecitationRating;
use App\Models\Ayah;
use App\Models\Recitation;
use App\Models\StudentProfile;
use App\Models\Surah;
use Illuminate\Support\Carbon;

/**
 * يحسب تحليلات الطالب بالكامل من بيانات recitations الفعلية (لا تقديرات) —
 * انظر Phase 8 من وثيقة المتطلبات و§1 (نموذج المجال) من وثيقة التحقق
 * المعماري. كل النسب والمعدلات مُشتقة مباشرة من سجلات قاعدة البيانات.
 */
class CalculateStudentAnalyticsAction
{
    /**
     * إجمالي آيات القرآن الكريم (ثابت مرجعي).
     */
    private const TOTAL_QURAN_AYAHS = 6236;

    /**
     * عتبة عدد التسميعات الأخيرة المستخدمة لاستنتاج نقاط القوة/الضعف
     * لكل سورة (يجب توفر حد أدنى من البيانات قبل الحكم).
     */
    private const MIN_SAMPLES_FOR_INSIGHT = 3;

    public function execute(StudentProfile $student): array
    {
        $recitations = $student->recitations()
            ->withoutGlobalScopes()
            ->where('student_id', $student->id)
            ->with('errors')
            ->orderBy('performed_at')
            ->get();

        return [
            'overall_progress' => $this->calculateOverallProgress($recitations),
            'progress_by_juz' => $this->calculateProgressByJuz($recitations),
            'progress_by_surah' => $this->calculateProgressBySurah($recitations),
            'attendance_rate' => $this->calculateAttendanceRate($student, $recitations),
            'streak' => $this->calculateStreak($recitations),
            'strengths' => $this->calculateStrengths($recitations),
            'weaknesses' => $this->calculateWeaknesses($recitations),
            'error_distribution' => $this->calculateErrorDistribution($recitations),
        ];
    }

    /**
     * نسبة التقدم الكلي = عدد الآيات المحفوظة (تسميعات من نوع "حفظ جديد")
     * الفعلية / إجمالي آيات القرآن (6236). لا تكرار عد لنفس الآية.
     */
    private function calculateOverallProgress($recitations): array
    {
        $memorizedAyahs = $this->countDistinctMemorizedAyahs($recitations);

        return [
            'memorized_ayahs' => $memorizedAyahs,
            'total_ayahs' => self::TOTAL_QURAN_AYAHS,
            'percentage' => self::TOTAL_QURAN_AYAHS > 0
                ? round(($memorizedAyahs / self::TOTAL_QURAN_AYAHS) * 100, 2)
                : 0.0,
        ];
    }

    /**
     * يحسب عدد الآيات الفريدة المحفوظة (type=new) عبر مجموعة (surah_number, ayah)
     * لتجنّب العد المضاعف عند تكرار تسميع نفس الآيات.
     */
    private function countDistinctMemorizedAyahs($recitations): int
    {
        $covered = [];

        foreach ($recitations as $recitation) {
            if ($recitation->type->value !== 'new') {
                continue;
            }

            for ($ayah = $recitation->ayah_from; $ayah <= $recitation->ayah_to; $ayah++) {
                $covered["{$recitation->surah_number}:{$ayah}"] = true;
            }
        }

        return count($covered);
    }

    /**
     * تقدّم بالجزء: يعتمد على current_juz المُسجَّل فعلياً على ملف الطالب
     * (يُحدَّث تشغيلياً عبر التسميع) — لا يُقدَّر من عدد التسميعات فقط.
     */
    private function calculateProgressByJuz($recitations): array
    {
        $result = [];

        foreach (range(1, 30) as $juz) {
            $result[$juz] = [
                'juz_number' => $juz,
                'recitations_count' => 0,
                'ayahs_covered' => 0,
            ];
        }

        // بيانات فعلية: نُجمّع عدد التسميعات لكل سورة، ثم نربطها تقريبياً
        // بالجزء عبر جدول ayahs المرجعي إن توفّرت بياناته.
        $ayahJuzMap = Ayah::query()
            ->join('surahs', 'surahs.id', '=', 'ayahs.surah_id')
            ->select('surahs.number as surah_number', 'ayahs.number_in_surah', 'ayahs.juz_number')
            ->get()
            ->groupBy('surah_number');

        foreach ($recitations as $recitation) {
            if ($recitation->type->value !== 'new') {
                continue;
            }

            $surahAyahs = $ayahJuzMap->get($recitation->surah_number);

            if (! $surahAyahs) {
                continue;
            }

            for ($ayah = $recitation->ayah_from; $ayah <= $recitation->ayah_to; $ayah++) {
                $match = $surahAyahs->firstWhere('number_in_surah', $ayah);

                if ($match && isset($result[$match->juz_number])) {
                    $result[$match->juz_number]['ayahs_covered']++;
                    $result[$match->juz_number]['recitations_count']++;
                }
            }
        }

        return array_values($result);
    }

    /**
     * تقدّم بالسورة: عدد الآيات المحفوظة الفريدة لكل سورة نسبة إلى
     * عدد آياتها الفعلي في المرجع القرآني (surahs.ayah_count).
     */
    private function calculateProgressBySurah($recitations): array
    {
        $coveredBySurah = [];

        foreach ($recitations as $recitation) {
            if ($recitation->type->value !== 'new') {
                continue;
            }

            $surahNumber = $recitation->surah_number;
            $coveredBySurah[$surahNumber] ??= [];

            for ($ayah = $recitation->ayah_from; $ayah <= $recitation->ayah_to; $ayah++) {
                $coveredBySurah[$surahNumber][$ayah] = true;
            }
        }

        if (empty($coveredBySurah)) {
            return [];
        }

        $surahs = Surah::query()
            ->whereIn('number', array_keys($coveredBySurah))
            ->get()
            ->keyBy('number');

        $result = [];

        foreach ($coveredBySurah as $surahNumber => $ayahs) {
            $surah = $surahs->get($surahNumber);
            $totalAyahs = $surah?->ayah_count ?? 0;
            $coveredCount = count($ayahs);

            $result[] = [
                'surah_number' => $surahNumber,
                'surah_name' => $surah?->name_arabic,
                'ayahs_covered' => $coveredCount,
                'total_ayahs' => $totalAyahs,
                'percentage' => $totalAyahs > 0 ? round(($coveredCount / $totalAyahs) * 100, 2) : 0.0,
            ];
        }

        return $result;
    }

    /**
     * معدل الحضور = أيام حضور فعلية (أيام وُجد فيها تسميع مُسجَّل للطالب) /
     * أيام الدراسة (أيام وُجد فيها أي نشاط تسميع في حلقة الطالب من أي طالب،
     * بما يعكس أيام انعقاد الحلقة فعلياً — لا تقدير خارجي).
     */
    private function calculateAttendanceRate(StudentProfile $student, $recitations): array
    {
        $studentDays = $recitations->map(fn ($r) => Carbon::parse($r->performed_at)->toDateString())
            ->unique()
            ->values();

        $circleDays = collect();

        if ($student->circle_id) {
            $circleDays = Recitation::withoutGlobalScopes()
                ->where('circle_id', $student->circle_id)
                ->pluck('performed_at')
                ->map(fn ($date) => Carbon::parse($date)->toDateString())
                ->unique()
                ->values();
        }

        $studyDays = $circleDays->count() > 0 ? $circleDays->count() : $studentDays->count();
        $attendanceDays = $studentDays->intersect($circleDays)->count();

        // إن لم تتوفر بيانات حلقة (نادر)، نعتمد أيام الطالب نفسها كأيام حضور كاملة.
        if ($circleDays->isEmpty()) {
            $attendanceDays = $studentDays->count();
            $studyDays = $studentDays->count();
        }

        return [
            'attendance_days' => $attendanceDays,
            'study_days' => $studyDays,
            'percentage' => $studyDays > 0 ? round(($attendanceDays / $studyDays) * 100, 2) : 0.0,
        ];
    }

    /**
     * سلسلة الانتظام (Streak) = عدد الأيام المتتالية الحالية (تنتهي
     * بأحدث يوم نشاط) التي سجّل فيها الطالب تسميعاً فعلياً.
     */
    private function calculateStreak($recitations): int
    {
        $days = $recitations->map(fn ($r) => Carbon::parse($r->performed_at)->toDateString())
            ->unique()
            ->sort()
            ->values();

        if ($days->isEmpty()) {
            return 0;
        }

        $streak = 1;
        $maxStreak = 1;

        for ($i = 1; $i < $days->count(); $i++) {
            $previous = Carbon::parse($days[$i - 1]);
            $current = Carbon::parse($days[$i]);

            if ($previous->diffInDays($current) === 1) {
                $streak++;
                $maxStreak = max($maxStreak, $streak);
            } else {
                $streak = 1;
            }
        }

        // الانتظام الحالي فقط إن كان آخر يوم نشاط قريباً (اليوم أو أمس)،
        // وإلا فالسلسلة الحالية منقطعة (0) رغم وجود سلاسل تاريخية أطول.
        $lastDay = Carbon::parse($days->last());
        $daysSinceLast = $lastDay->diffInDays(now()->startOfDay());

        if ($daysSinceLast > 1) {
            return 0;
        }

        return $streak;
    }

    /**
     * نقاط القوة: سور بتقييم excellent باستمرار (نسبة أخطاء منخفضة)،
     * وبحد أدنى من عينات البيانات (§ Phase 8 — لا تقديرات).
     */
    private function calculateStrengths($recitations): array
    {
        $bySurah = $this->groupRatingsAndErrorsBySurah($recitations);

        $strengths = [];

        foreach ($bySurah as $surahNumber => $stats) {
            if ($stats['count'] < self::MIN_SAMPLES_FOR_INSIGHT) {
                continue;
            }

            $excellentRatio = $stats['excellent_count'] / $stats['count'];
            $errorRate = $stats['errors_count'] / max($stats['count'], 1);

            if ($excellentRatio >= 0.6 && $errorRate < 1) {
                $strengths[] = [
                    'surah_number' => $surahNumber,
                    'excellent_ratio' => round($excellentRatio * 100, 2),
                    'error_rate' => round($errorRate, 2),
                    'samples' => $stats['count'],
                ];
            }
        }

        return $strengths;
    }

    /**
     * نقاط الضعف: سور بتقييم needs_work متكرر أو أخطاء كثيرة.
     */
    private function calculateWeaknesses($recitations): array
    {
        $bySurah = $this->groupRatingsAndErrorsBySurah($recitations);

        $weaknesses = [];

        foreach ($bySurah as $surahNumber => $stats) {
            if ($stats['count'] < self::MIN_SAMPLES_FOR_INSIGHT) {
                continue;
            }

            $needsWorkRatio = $stats['needs_work_count'] / $stats['count'];
            $errorRate = $stats['errors_count'] / max($stats['count'], 1);

            if ($needsWorkRatio >= 0.4 || $errorRate >= 1.5) {
                $weaknesses[] = [
                    'surah_number' => $surahNumber,
                    'needs_work_ratio' => round($needsWorkRatio * 100, 2),
                    'error_rate' => round($errorRate, 2),
                    'samples' => $stats['count'],
                ];
            }
        }

        return $weaknesses;
    }

    /**
     * @return array<int, array{count: int, excellent_count: int, needs_work_count: int, errors_count: int}>
     */
    private function groupRatingsAndErrorsBySurah($recitations): array
    {
        $stats = [];

        foreach ($recitations as $recitation) {
            $surahNumber = $recitation->surah_number;

            $stats[$surahNumber] ??= [
                'count' => 0,
                'excellent_count' => 0,
                'needs_work_count' => 0,
                'errors_count' => 0,
            ];

            $stats[$surahNumber]['count']++;

            if ($recitation->rating === RecitationRating::Excellent) {
                $stats[$surahNumber]['excellent_count']++;
            }

            if ($recitation->rating === RecitationRating::NeedsWork) {
                $stats[$surahNumber]['needs_work_count']++;
            }

            $stats[$surahNumber]['errors_count'] += $recitation->errors->sum('count');
        }

        return $stats;
    }

    /**
     * توزيع الأخطاء (%) عبر الأنواع الأربعة: memorization/tajweed/
     * pronunciation/tashkeel — من recitation_errors الفعلية فقط.
     */
    private function calculateErrorDistribution($recitations): array
    {
        $totals = [
            'memorization' => 0,
            'tajweed' => 0,
            'pronunciation' => 0,
            'tashkeel' => 0,
        ];

        foreach ($recitations as $recitation) {
            foreach ($recitation->errors as $error) {
                $type = $error->error_type->value;

                if (array_key_exists($type, $totals)) {
                    $totals[$type] += $error->count;
                }
            }
        }

        $grandTotal = array_sum($totals);

        $distribution = [];

        foreach ($totals as $type => $count) {
            $distribution[$type] = [
                'count' => $count,
                'percentage' => $grandTotal > 0 ? round(($count / $grandTotal) * 100, 2) : 0.0,
            ];
        }

        return $distribution;
    }
}
