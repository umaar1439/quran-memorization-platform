import type { PaginatedResponse } from '@/Types';

/**
 * عقد وحدة حضور الطلاب.
 *
 * الصفحات (Teacher/AttendanceSheet وAdmin/Attendances/Index) تُرجع Inertia
 * props لا موارد JSON، لذلك يُوصَف هنا شكل ما تُسلسِله
 * StudentAttendanceQuery::dailySheet فعلياً (مصفوفات مبنية يدوياً) وشكل صف
 * StudentAttendance كما تُسلسِله Eloquent في سجل الحضور، بلا أي حقل وهمي.
 */

/** يطابق App\Enums\AttendanceStatus. */
export type AttendanceStatusValue = 'present' | 'absent' | 'late' | 'excused';

/** صف ورقة الحضور: طالب + حالته في التاريخ المعروض (null = لم يُسجَّل). */
export type AttendanceSheetRow = {
    student_id: number;
    student_name: string;
    enrollment_number: string;
    circle_id: number | null;
    status: AttendanceStatusValue | null;
    notes: string | null;
    attendance_id: number | null;
    recorded_at: string | null;
};

/** حلقة في قائمة اختيار الورقة (StudentAttendanceQuery::circlesFor). */
export type AttendanceCircleOption = {
    id: number;
    name: string;
    mosque_name: string | null;
};

/** عدّاد الحالات + unrecorded (طلاب لم تُحدَّد حالتهم بعد). */
export type AttendanceSummary = Record<string, number>;

/** ورقة الحضور كاملة كما تصل في props.sheet. */
export type AttendanceSheet = {
    /** YYYY-MM-DD */
    date: string;
    circle_id: number | null;
    circles: AttendanceCircleOption[];
    rows: AttendanceSheetRow[];
    summary: AttendanceSummary;
};

/** صف سجل الحضور كما تُسلسِله Eloquent (مع علاقات محمَّلة مسبقاً). */
export type AttendanceHistoryRow = {
    id: number;
    student_id: number;
    circle_id: number | null;
    /** YYYY-MM-DD (cast 'date' ⇒ ISO 8601 كامل في JSON) */
    date: string;
    status: AttendanceStatusValue;
    notes: string | null;
    recorded_by: number | null;
    created_at: string | null;
    updated_at: string | null;
    student?: {
        id: number;
        user_id: number;
        enrollment_number: string;
        user?: { id: number; name: string } | null;
    } | null;
    circle?: { id: number; name: string } | null;
    recorder?: { id: number; name: string } | null;
};

/** حمولة حفظ الورقة — نفس مفاتيح BulkAttendanceRequest. */
export type AttendanceBulkRecordPayload = {
    student_id: number;
    status: AttendanceStatusValue;
    notes: string | null;
};

export type AttendanceBulkPayload = {
    circle_id: number;
    /** YYYY-MM-DD */
    date: string;
    records: AttendanceBulkRecordPayload[];
};

/** فلاتر صفحة الإدارة — نفس أسماء معاملات StudentAttendanceController. */
export type AttendanceFilters = {
    circle_id?: string | number;
    student_id?: string | number;
    status?: string;
    date?: string;
};

export type AttendanceFilterKey = 'circle_id' | 'student_id' | 'status' | 'date';

export type AttendanceSheetPageProps = {
    sheet: AttendanceSheet;
};

export type AttendanceIndexPageProps = AttendanceSheetPageProps & {
    attendances: PaginatedResponse<AttendanceHistoryRow>;
    filters: AttendanceFilters;
};
