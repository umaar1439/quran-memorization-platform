import { router } from '@inertiajs/vue3';
import type { AuditLogFilters } from '@/Types/api/auditLog';

/**
 * طبقة Model/API لسجل التدقيق: تنقّل Inertia فقط (قراءة بحتة).
 *
 * الفلاتر الفارغة تُحذف قبل بناء الرابط حتى لا يمتلئ العنوان بـ
 * `event=&from=&to=` بلا أثر على النتيجة.
 */
export const AuditLogApi = {
    visit(filters: AuditLogFilters, page?: number, replace = false): void {
        const query: AuditLogFilters & { page?: number } = { ...filters };

        if (page !== undefined) {
            query.page = page;
        }

        (Object.keys(query) as (keyof typeof query)[]).forEach((key) => {
            const value = query[key];

            if (value === undefined || value === null || value === '') {
                delete query[key];
            }
        });

        router.get(route('admin.audit-logs.page'), query, { preserveState: true, replace });
    },
};
