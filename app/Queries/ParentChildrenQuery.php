<?php

declare(strict_types=1);

namespace App\Queries;

use App\Actions\CalculateStudentAnalyticsAction;
use App\Models\Guardian;
use App\Models\StudentProfile;

/**
 * بيانات صفحة "أبنائي": الطلاب المرتبطون بولي الأمر مع ملخص تقدم كل ابن.
 * يُعيد استخدام نفس Action المستخدم في لوحة الطالب لتفادي ازدواجية منطق
 * الحساب (مصدر حقيقة واحد).
 */
class ParentChildrenQuery
{
    public function __construct(
        private readonly CalculateStudentAnalyticsAction $calculateAnalytics,
    ) {}

    /**
     * @return array<int, array{student: StudentProfile, overall_progress: int, attendance_rate: int}>
     */
    public function summaries(Guardian $guardian): array
    {
        return $guardian->students()
            ->with(['user', 'mosque', 'circle'])
            ->get()
            ->map(function (StudentProfile $student): array {
                $analytics = $this->calculateAnalytics->execute($student);

                return [
                    'student' => $student,
                    'overall_progress' => $analytics['overall_progress'],
                    'attendance_rate' => $analytics['attendance_rate'],
                ];
            })
            ->all();
    }
}
