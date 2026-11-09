/**
 * عقد API التسميع — يطابق App\Http\Resources\RecitationResource حقلاً بحقل.
 *
 * منفصل عن Types/index.d.ts (نموذج صفحة Inertia) لأن الاستجابة الفعلية لا
 * تحتوي organization_id ولا idempotency_key؛ النوع القديم كان يَعِد بحقلين
 * غير موجودين، وهو ما يجعل الـ ViewModel يعتمد على وهم لا على العقد.
 */

export type ApiEnvelope<T> = { data: T };

export type RecitationTypeValue = 'new' | 'near_revision' | 'far_revision';

export type RecitationRatingValue = 'excellent' | 'very_good' | 'good' | 'needs_work';

/** نفس مفاتيح StoreRecitationRequest + مسار المزامنة دون اتصال (لا تُغيَّر). */
export type RecitationCreatePayload = {
    student_id: number | string;
    type: RecitationTypeValue;
    surah_number: number;
    ayah_from: number;
    ayah_to: number;
    rating: RecitationRatingValue;
    notes: string;
    idempotency_key: string;
};

/** يطابق App\Http\Resources\UserSummaryResource. */
export type RecitationUserSummary = {
    id: number;
    name: string;
    locale?: string | null;
    status?: string | null;
};

/** يطابق App\Http\Resources\StudentProfileResource. */
export type RecitationStudentSummary = {
    id: number;
    user_id: number;
    mosque_id?: number | null;
    circle_id?: number | null;
    enrollment_number?: string | null;
    birth_date?: string | null;
    current_juz?: number | null;
    status?: string | null;
    user?: RecitationUserSummary;
};

/** يطابق App\Http\Resources\RecitationErrorResource. */
export type RecitationErrorRow = {
    id: number;
    recitation_id: number;
    error_type: string;
    count: number;
    notes?: string | null;
};

export type RecitationResponse = {
    id: number;
    student_id: number;
    teacher_id: number;
    mosque_id: number | null;
    circle_id: number | null;
    type: RecitationTypeValue;
    surah_number: number;
    ayah_from: number;
    ayah_to: number;
    rating: RecitationRatingValue | null;
    notes: string | null;
    performed_at: string | null;
    created_at: string | null;
    errors?: RecitationErrorRow[];
    student?: RecitationStudentSummary;
};

/**
 * الحقول التي تعرضها قائمة "آخر التسميعات" فقط، فتُقبل من Inertia props ومن
 * استجابة API معاً دون أي حقل وهمي.
 */
export type RecitationListItem = {
    id: number;
    surah_number: number;
    ayah_from: number;
    ayah_to: number;
    rating?: RecitationRatingValue | string | null;
    student?: { user?: { name: string } };
};
