/**
 * عقد وحدة الامتحانات والنتائج — نوعان منفصلان عن قصد:
 *
 * 1) `*Response`  : يطابق App\Http\Resources\{Exam,ExamSection,ExamResult}Resource
 *                   حرفياً (استجابة JSON لمسارات exams.* / exam-results.*).
 * 2) `*PageModel` : ما تُسلسِله Eloquent فعلياً داخل خصائص صفحات Inertia
 *                   (الـ Controllers تمرّر Paginator نماذج، لا Resource).
 *
 * الفصل ضروري لأن الشكلين مختلفان: استجابة الـ Resource لا تحتوي
 * `organization_id` ولا `mosque`/`circle`، بينما خصائص الصفحة تحتويها.
 * دمجُهما في واجهة واحدة يجعل الـ ViewModel يعتمد على حقل وهمي.
 */

import type { PaginatedResponse } from '@/Types';

/** غلاف JsonResource الافتراضي (لا يوجد withoutWrapping في المشروع). */
export type ApiEnvelope<T> = { data: T };

/** يطابق App\Enums\ExamStatus. */
export type ExamStatusValue = 'draft' | 'scheduled' | 'in_progress' | 'completed' | 'approved';

/** يطابق App\Enums\ExamSectionType. */
export type ExamSectionTypeValue = 'recitation' | 'tajweed' | 'tafseer';

/** يطابق App\Enums\ExamResultStatus. */
export type ExamResultStatusValue = 'draft' | 'submitted' | 'approved';

/** الحالات التي يقبلها StoreExamResultRequest عند التسجيل (الاعتماد له مسار آخر). */
export type ExamResultCreatableStatus = Extract<ExamResultStatusValue, 'draft' | 'submitted'>;

/* ------------------------------------------------------------------ *
 * الحمولات (نفس مفاتيح FormRequest — لا حقل واحد أكثر)
 * ------------------------------------------------------------------ */

/** يطابق StoreExamRequest. */
export type ExamCreatePayload = {
    mosque_id: number | string;
    circle_id: number | string;
    title: string;
    exam_type: string;
    scheduled_at: string;
};

/** يطابق StoreExamResultRequest (+ exam_id من الصفحة). */
export type ExamResultCreatePayload = {
    exam_id: number;
    exam_section_id: number | string;
    student_id: number | string;
    score: number | string;
    grade: string;
    status: ExamResultCreatableStatus;
};

/** يطابق CorrectExamResultRequest. */
export type ExamCorrectionPayload = {
    score: number | string;
    grade: string;
    reason: string;
};

/* ------------------------------------------------------------------ *
 * استجابات الـ API (Resources)
 * ------------------------------------------------------------------ */

/** يطابق App\Http\Resources\ExamSectionResource. */
export type ExamSectionResponse = {
    id: number;
    exam_id: number;
    surah_number: number | null;
    ayah_from: number | null;
    ayah_to: number | null;
    section_type: ExamSectionTypeValue;
    max_score: number;
};

/** يطابق App\Http\Resources\ExamResultResource (student عند تحميلها فقط). */
export type ExamResultResponse = {
    id: number;
    exam_id: number;
    exam_section_id: number;
    student_id: number;
    score: number | null;
    grade: string | null;
    examiner_id: number;
    status: ExamResultStatusValue;
    approved_by: number | null;
    approved_at: string | null;
    created_at: string | null;
    student?: {
        id: number;
        user_id: number;
        enrollment_number?: string | null;
        user?: { id: number; name: string };
    };
};

/** يطابق App\Http\Resources\ExamResource. */
export type ExamResponse = {
    id: number;
    mosque_id: number | null;
    circle_id: number | null;
    title: string;
    exam_type: string;
    scheduled_at: string | null;
    status: ExamStatusValue;
    created_by: number | null;
    created_at: string | null;
    sections?: ExamSectionResponse[];
    results?: ExamResultResponse[];
};

/* ------------------------------------------------------------------ *
 * نماذج صفحات Inertia (تسلسل Eloquent للنموذج)
 * ------------------------------------------------------------------ */

export type ExamSectionPageModel = {
    id: number;
    exam_id: number;
    organization_id: number;
    surah_number: number | null;
    ayah_from: number | null;
    ayah_to: number | null;
    section_type: ExamSectionTypeValue;
    max_score: number;
};

export type ExamPageModel = {
    id: number;
    organization_id: number;
    mosque_id: number | null;
    circle_id: number | null;
    title: string;
    exam_type: string;
    scheduled_at: string | null;
    status: ExamStatusValue;
    created_by: number;
    created_at: string | null;
    updated_at: string | null;
    sections?: ExamSectionPageModel[];
    mosque?: { id: number; name: string } | null;
    circle?: { id: number; name: string; mosque_id: number | null } | null;
};

export type ExamResultPageModel = {
    id: number;
    exam_id: number;
    exam_section_id: number;
    student_id: number;
    organization_id: number;
    score: number | null;
    grade: string | null;
    examiner_id: number;
    status: ExamResultStatusValue;
    approved_by: number | null;
    approved_at: string | null;
    created_at: string | null;
    exam?: Pick<ExamPageModel, 'id' | 'title'>;
    student?: {
        id: number;
        user_id: number;
        user?: { id: number; name: string };
    };
};

/* ------------------------------------------------------------------ *
 * خصائص الصفحات (Controller ⇄ View)
 * ------------------------------------------------------------------ */

export type ExamIndexPageProps = {
    exams: PaginatedResponse<ExamPageModel>;
    mosques: { id: number; name: string }[];
    circles: { id: number; name: string; mosque_id: number | null }[];
    filters: { mosque_id?: string; circle_id?: string };
};

export type ExamResultsPageProps = {
    exam: ExamPageModel;
    results: PaginatedResponse<ExamResultPageModel>;
};

export type StudentExamsPageProps = {
    results: PaginatedResponse<ExamResultPageModel>;
};
