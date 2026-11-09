import type { PaginatedResponse } from '@/Types';

/**
 * عقد سجل نشاط المعلم (Teacher Activity) — صفحة Teacher/Attendance
 * على المسار teacher.activity.page.
 *
 * السجل مُشتق تلقائياً من نشاط المعلم الفعلي (TeacherAttendance) وليس له
 * أي مسار كتابة؛ الصفحة قراءة وترقيم فقط.
 */

/** ما تُسلسِله Eloquent داخل attendances.data. */
export type TeacherActivityPageModel = {
    id: number;
    organization_id: number;
    teacher_id: number;
    mosque_id: number | null;
    /** cast 'date' ⇒ ISO 8601 كامل، لا YYYY-MM-DD. */
    date: string;
    first_activity_at: string | null;
    last_activity_at: string | null;
    actions_count: number;
};

export type TeacherActivityIndexPageProps = {
    attendances: PaginatedResponse<TeacherActivityPageModel>;
};
