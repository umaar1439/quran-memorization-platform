import type { PaginatedResponse } from '@/Types';

/**
 * عقد وحدة نقل الطلاب (Student Transfers) — صفحة Admin/StudentTransfers/Index.
 *
 * `admin.student-transfers.page` يمرّر StudentTransferQuery::paginate
 * (Eloquent مع with('student.user','fromMosque','toMosque')) — والنوع
 * `student-transfers.store` يُرجع JSON (201) بـ StudentTransferResource.
 */

/** ما تُسلسِله Eloquent داخل transfers.data. */
export type StudentTransferPageModel = {
    id: number;
    organization_id: number;
    student_id: number;
    from_mosque_id: number | null;
    to_mosque_id: number;
    from_circle_id: number | null;
    to_circle_id: number | null;
    transferred_by_user_id: number;
    reason: string | null;
    /** cast 'datetime' ⇒ ISO 8601 كامل. */
    transferred_at: string;
    student?: { id: number; user?: { name: string } | null } | null;
    from_mosque?: { id: number; name: string } | null;
    to_mosque?: { id: number; name: string } | null;
};

/** خيار طالب في نموذج النقل (StudentTransferQuery::studentOptions). */
export type StudentTransferStudentOption = {
    id: number;
    user?: { name: string } | null;
};

/** خيار مسجد في نموذج النقل (MosqueOptionsQuery::all). */
export type StudentTransferMosqueOption = {
    id: number;
    name: string;
};

export type StudentTransferIndexPageProps = {
    transfers: PaginatedResponse<StudentTransferPageModel>;
    mosques: StudentTransferMosqueOption[];
    students: StudentTransferStudentOption[];
};

/** حمولة النقل — نفس مفاتيح TransferStudentRequest (بلا to_circle_id). */
export type StudentTransferFormPayload = {
    student_id: string | number;
    to_mosque_id: string | number;
    reason: string;
};
