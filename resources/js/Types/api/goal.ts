import type { PaginatedResponse } from '@/Types';

/**
 * عقد وحدة أهداف الطالب (Student Goals).
 *
 * صفحة `Student/Goals` تقرأ StudentGoalQuery::forStudent (Eloquent مع
 * with('surah'))؛ وواجهات الإنشاء/التعديل تُرجع StudentGoalResource عبر
 * JSON (student-goals.store / student-goals.update).
 */

/** يطابق App\Enums\GoalStatus. */
export type GoalStatusValue = 'active' | 'completed' | 'failed' | 'cancelled';

/** يطابق App\Enums\GoalTargetType. */
export type GoalTargetTypeValue = 'memorization' | 'revision';

/** يطابق App\Enums\PeriodType. */
export type PeriodTypeValue = 'daily' | 'weekly' | 'monthly';

/** السورة كما تُحمَّل مع الهدف (with('surah')). */
export type GoalSurahSummary = {
    id: number;
    number: number;
    name_ar: string;
    ayah_count?: number;
};

/** ما تُسلسِله Eloquent داخل goals.data. */
export type StudentGoalPageModel = {
    id: number;
    organization_id: number;
    student_id: number;
    period_type: PeriodTypeValue;
    target_type: GoalTargetTypeValue;
    surah_id: number | null;
    juz_number: number | null;
    target_amount: number;
    achieved_amount: number;
    completion_percentage: number;
    /** cast 'date' ⇒ ISO 8601 كامل. */
    period_start: string;
    period_end: string;
    status: GoalStatusValue;
    surah?: GoalSurahSummary | null;
};

export type StudentGoalsPageProps = {
    goals: PaginatedResponse<StudentGoalPageModel>;
};

/**
 * حمولة إنشاء هدف — نفس مفاتيح StoreStudentGoalRequest.
 * `surah_id`/`juz_number` نصّان من النموذج؛ الخادم يتحقق ويُطبِّع.
 */
export type StudentGoalFormPayload = {
    student_id: string | number;
    period_type: string;
    target_type: string;
    surah_id?: string | number;
    juz_number?: string | number;
    target_amount: string | number;
    period_start: string;
    period_end: string;
};
