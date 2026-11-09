import type { PaginatedResponse } from '@/Types';

/**
 * عقد وحدة التقارير (Reports) — صفحة Admin/Reports/Index.
 *
 * القائمة تُرسَل كـ Eloquent paginator (ReportDirectoryQuery::paginate) لا
 * JsonResource، والطلب يُرسَل إلى reports.store الذي يُرجع JSON (201) مع
 * ReportResource — لذلك نموذج الطلب هنا هو الحمولة فقط، ونموذج الصف هو
 * ما تُسلسِله Eloquent فعلياً.
 */

/** يطابق App\Enums\ReportStatus. */
export type ReportStatusValue = 'queued' | 'processing' | 'completed' | 'failed';

/** ما تُسلسِله Eloquent داخل reports.data. */
export type ReportPageModel = {
    id: number;
    organization_id: number;
    requested_by: number;
    report_type: string;
    parameters: Record<string, unknown> | null;
    status: ReportStatusValue;
    /** cast 'datetime' ⇒ ISO 8601 كامل. */
    expires_at: string | null;
    created_at: string | null;
};

export type ReportIndexPageProps = {
    reports: PaginatedResponse<ReportPageModel>;
};

/** حمولة طلب تقرير جديد — نفس مفاتيح StoreReportRequest. */
export type ReportFormPayload = {
    report_type: string;
};
