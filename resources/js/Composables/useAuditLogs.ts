import { reactive } from 'vue';
import { AuditLogApi } from '@/Services/AuditLogApi';
import type { AuditLogFilterForm, AuditLogIndexPageProps } from '@/Types/api/auditLog';

/**
 * ViewModel لسجل التدقيق (قراءة فقط): حالة شريط الفلاتر والتنقّل بين
 * الصفحات — والمكوّن يعرض فقط. لا يوجد أي إجراء كتابة على هذا السجل.
 */
export function useAuditLogs(props: AuditLogIndexPageProps) {
    const filters = reactive<AuditLogFilterForm>({
        event: props.filters.event ?? '',
        from: props.filters.from ?? '',
        to: props.filters.to ?? '',
    });

    /** يطبّق الفلاتر ويعود إلى الصفحة الأولى. */
    function applyFilters(): void {
        AuditLogApi.visit({ ...filters }, undefined, true);
    }

    function onPageChange(page: number): void {
        AuditLogApi.visit({ ...filters }, page);
    }

    return {
        filters,
        applyFilters,
        onPageChange,
    };
}
