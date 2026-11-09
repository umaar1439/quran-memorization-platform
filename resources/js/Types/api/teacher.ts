import type { PaginatedResponse } from '@/Types';

/**
 * عقد وحدة المعلمين (Teacher Management).
 *
 * مسارات admin.teachers.* تُرجع RedirectResponse (Inertia) لا JsonResource،
 * فلا يوجد wire contract بصيغة JSON؛ نُعرِّف نموذج الصف كما تُسلسِله Eloquent
 * فعلياً (TeacherDirectoryQuery::paginate مع with('user','user.circlesTaught'))
 * وحمولة النموذج كما تُرسَل إلى StoreTeacherRequest/UpdateTeacherRequest.
 */

/** يطابق App\Enums\UserStatus. */
export type TeacherStatusValue = 'active' | 'inactive' | 'suspended';

/** الحلقة كما تُحمَّل عبر user.circlesTaught. */
export type TeacherCircleSummary = {
    id: number;
    name: string;
};

/** حساب المستخدم كما يُحمَّل مع صف المعلم (with('user')). */
export type TeacherUserSummary = {
    id: number;
    name: string;
    email: string;
    phone: string | null;
    status: TeacherStatusValue;
    circlesTaught?: TeacherCircleSummary[];
};

/** ما تُسلسِله Eloquent داخل teachers.data. */
export type TeacherPageModel = {
    id: number;
    user_id: number;
    organization_id: number;
    specialization: string | null;
    ijazah_details: string | null;
    /** cast 'date' ⇒ ISO 8601 كامل، لا YYYY-MM-DD. */
    hired_at: string | null;
    created_at: string | null;
    user?: TeacherUserSummary | null;
};

/** فلاتر القائمة — نفس معاملات TeacherController::page. */
export type TeacherFilters = {
    search?: string;
};

/** عنصر خيارات الحلقات (CircleOptionsQuery::all مع with('mosque:id,name')). */
export type TeacherCircleOption = {
    id: number;
    name: string;
    mosque_id: number | null;
    mosque?: { name: string } | null;
};

export type TeacherIndexPageProps = {
    teachers: PaginatedResponse<TeacherPageModel>;
    circles: TeacherCircleOption[];
    filters: TeacherFilters;
};

/**
 * حقول نموذج الإنشاء/التعديل — نفس مفاتيح StoreTeacherRequest.
 * `password` يُرسَل فارغاً في التعديل بمعنى «لا تغيير»، و`circles` إلزامية
 * بحلقة واحدة على الأقل (الخادم يتحقق ويُرجع خطأ على المفتاح نفسه).
 */
export type TeacherFormPayload = {
    name: string;
    email: string;
    password: string;
    phone: string;
    specialization: string;
    ijazah_details: string;
    hired_at: string;
    status: string;
    circles: number[];
};
