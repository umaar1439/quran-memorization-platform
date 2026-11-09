import type { PaginatedResponse } from '@/Types';

/**
 * عقد وحدة الطلاب (Students & Enrollments).
 *
 * مسارات admin.students.* تُرجع RedirectResponse (Inertia) لا JsonResource،
 * فلا يوجد wire contract بصيغة JSON؛ نُعرِّف نموذج الصف كما تُسلسِله Eloquent
 * فعلياً (StudentDirectoryQuery::paginate مع with('user','circle','mosque'))
 * وحمولة النموذج كما تُرسَل إلى StoreStudentRequest/UpdateStudentRequest.
 */

/** يطابق App\Enums\StudentStatus. */
export type StudentStatusValue = 'active' | 'transferred' | 'graduated' | 'withdrawn';

/** حساب المستخدم كما يُحمَّل مع صف الطالب (with('user')). */
export type StudentUserSummary = {
    id: number;
    name: string;
    email: string;
};

/** الحلقة كما تُحمَّل مع الصف (with('circle')). */
export type StudentCircleSummary = {
    id: number;
    name: string;
    mosque_id: number;
};

/** المسجد كما يُحمَّل مع الصف (with('mosque')). */
export type StudentMosqueSummary = {
    id: number;
    name: string;
};

/** ما تُسلسِله Eloquent داخل students.data. */
export type StudentPageModel = {
    id: number;
    user_id: number;
    organization_id: number;
    mosque_id: number | null;
    circle_id: number | null;
    enrollment_number: string;
    /** cast 'date' ⇒ ISO 8601 كامل، لا YYYY-MM-DD. */
    birth_date: string | null;
    current_juz: number | null;
    status: StudentStatusValue;
    created_at: string | null;
    user?: StudentUserSummary | null;
    circle?: StudentCircleSummary | null;
    mosque?: StudentMosqueSummary | null;
};

/** فلاتر القائمة — نفس أسماء معاملات StudentController::page. */
export type StudentFilters = {
    search?: string;
    circle_id?: string | number;
    mosque_id?: string | number;
    status?: string;
};

/** مفاتيح الفلاتر القابلة للتغيير من شريط الفلاتر. */
export type StudentFilterKey = 'circle_id' | 'mosque_id' | 'status';

/** عنصر خيارات الحلقات (CircleOptionsQuery::all). */
export type StudentCircleOption = {
    id: number;
    name: string;
    mosque_id: number;
    mosque?: { name: string } | null;
};

/** عنصر خيارات المساجد (MosqueOptionsQuery::all). */
export type StudentMosqueOption = {
    id: number;
    name: string;
};

export type StudentIndexPageProps = {
    students: PaginatedResponse<StudentPageModel>;
    circles: StudentCircleOption[];
    mosques: StudentMosqueOption[];
    filters: StudentFilters;
};

/**
 * حقول نموذج الإنشاء/التعديل — نفس مفاتيح StoreStudentRequest.
 * الحقول الرقمية/التاريخية تصل نصوصاً من FormInput/FormSelect، والخادم
 * يتحقق منها ويُطبِّع الفراغ إلى null داخل StudentData.
 * `password` يُرسَل فارغاً في التعديل بمعنى «لا تغيير».
 */
export type StudentFormPayload = {
    name: string;
    email: string;
    password: string;
    enrollment_number: string;
    birth_date: string;
    current_juz: string | number;
    circle_id: string | number;
    status: string;
};
