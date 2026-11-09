import type { PaginatedResponse } from '@/Types';

/**
 * عقد وحدة الحلقات.
 *
 * admin.circles.* تُرجع RedirectResponse (Inertia) لا JsonResource؛ نُعرِّف
 * نموذج الصف كما تُسلسِله Eloquent فعلياً (CircleDirectoryQuery مع
 * with('mosque','teacher') وwithCount('student_profiles')).
 */

/** يطابق App\Enums\GenericStatus. */
export type CircleStatusValue = 'active' | 'inactive';

/** المسجد كما يُحمَّل مع صف الحلقة (with('mosque')). */
export type CircleMosqueSummary = {
    id: number;
    name: string;
};

/** مسؤول الحلقة كما يُحمَّل مع الصف (with('teacher')) — قد يكون non-assigned. */
export type CircleTeacherSummary = {
    id: number;
    name: string;
};

/** ما تُسلسِله Eloquent داخل circles.data. */
export type CirclePageModel = {
    id: number;
    organization_id: number;
    mosque_id: number;
    teacher_id: number | null;
    name: string;
    level: string | null;
    capacity: number | null;
    status: CircleStatusValue;
    created_at: string | null;
    mosque?: CircleMosqueSummary | null;
    teacher?: CircleTeacherSummary | null;
    /** عمود withCount('student_profiles'). */
    student_profiles_count: number;
};

export type CircleFilters = {
    search?: string;
    mosque_id?: string | number;
};

/** عنصر قوائم الاختيار (MosqueOptionsQuery / UserOptionsQuery). */
export type CircleOptionRef = {
    id: number;
    name: string;
};

export type CircleIndexPageProps = {
    circles: PaginatedResponse<CirclePageModel>;
    mosques: CircleOptionRef[];
    teachers: CircleOptionRef[];
    filters: CircleFilters;
};

/**
 * حقول نموذج الإنشاء/التعديل — نفس مفاتيح StoreCircleRequest.
 * mosque_id/teacher_id/capacity تصل نصوصاً من FormSelect/FormInput،
 * والخادم يتحقق منها ويُطبِّع الفراغ إلى null داخل CircleData.
 */
export type CircleFormPayload = {
    mosque_id: string | number;
    teacher_id: string | number;
    name: string;
    level: string;
    capacity: string | number;
    status: string;
};
