import type { PaginatedResponse } from '@/Types';

/**
 * عقد وحدة سجل التدقيق (Audit Logs) — صفحة Admin/AuditLogs/Index.
 *
 * صفحة القراءة فقط: `admin.audit-logs.page` يمرّر AuditLogQuery::paginate
 * (مجموعة Eloquent مع with('user')) وفلاتر الطلب، ولا يوجد أي مسار كتابة
 * (Append-Only).
 */

/** ما تُسلسِله Eloquent داخل auditLogs.data. */
export type AuditLogPageModel = {
    id: number;
    organization_id: number;
    user_id: number | null;
    event: string;
    auditable_type: string | null;
    auditable_id: number | null;
    old_values: Record<string, unknown> | null;
    new_values: Record<string, unknown> | null;
    ip_address: string | null;
    request_id: string | null;
    created_at: string | null;
    user?: { id: number; name: string } | null;
};

/** معاملات الفلترة المدعومة في AuditLogController::page. */
export type AuditLogFilters = {
    event?: string;
    user_id?: string | number;
    from?: string;
    to?: string;
};

export type AuditLogIndexPageProps = {
    auditLogs: PaginatedResponse<AuditLogPageModel>;
    filters: AuditLogFilters;
};

/** الحقول القابلة للتعديل في شريط الفلاتر. */
export type AuditLogFilterForm = {
    event: string;
    from: string;
    to: string;
};
