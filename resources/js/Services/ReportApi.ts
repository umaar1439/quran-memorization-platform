import { router } from '@inertiajs/vue3';
import { postJson } from '@/Services/HttpJson';
import type { ReportFormPayload } from '@/Types/api/report';

/**
 * طبقة Model/API لوحدة التقارير: تعرف مسار طلب التقرير (JSON)، ومسار
 * التحميل المُصرَّح به، ومسار صفحة القائمة (Inertia). لا تُستدعى من
 * المكوّنات مباشرة.
 */
export const ReportApi = {
    /** ينشئ طلب تقرير (202/201 JSON) ويُعيد السجل المُنشأ. */
    create(payload: ReportFormPayload): Promise<unknown> {
        return postJson(route('reports.store'), payload);
    },

    /** رابط التحميل المُصرَّح به — يفتحه الـ ViewModel في تبويب جديد. */
    downloadUrl(reportId: number): string {
        return route('reports.download', reportId);
    },

    /** تنقّل Inertia بين صفحات القائمة فقط. */
    visit(page: number): void {
        router.get(route('admin.reports.page'), { page }, { preserveState: true });
    },

    /** يعيد تحميل قائمة التقارير بعد إنشاء طلب جديد. */
    reload(): void {
        router.reload({ only: ['reports'] });
    },
};
