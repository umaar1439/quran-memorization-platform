import { router } from '@inertiajs/vue3';
import { postJson } from '@/Services/HttpJson';
import type { StudentTransferFormPayload } from '@/Types/api/studentTransfer';

/**
 * طبقة Model/API لنقل الطلاب: مسار النقل يُرجع JSON (201)، ومسار الصفحة
 * يُرجع Inertia response. كلاهما هنا، فلا تعرف الصفحة أي مسار.
 *
 * الأخطاء تُرمى كـ ApiFailure {errors, message} (انظر HttpJson) فيتعامل
 * الـ ViewModel مع التحقق والفشل العام بنفس الشكل.
 */
export const StudentTransferApi = {
    /** ينقل طالباً إلى مسجد آخر ويُعيد سجل النقل المُنشأ. */
    create(payload: StudentTransferFormPayload): Promise<unknown> {
        return postJson(route('student-transfers.store'), payload);
    },

    /** يعيد تحميل جدول النقل فقط بعد نجاح العملية. */
    reload(): void {
        router.reload({ only: ['transfers'] });
    },

    /** تنقّل Inertia بين صفحات السجل. */
    visit(page: number): void {
        router.get(route('admin.student-transfers.page'), { page }, { preserveState: true });
    },
};
