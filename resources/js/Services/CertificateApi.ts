import { router } from '@inertiajs/vue3';

/**
 * طبقة Model/API لشهادات المستخدم الحالي: تنقّل Inertia فقط (عرض للقراءة).
 * إصدار الشهادات يحدث من واجهة الإدارة عبر JSON وليس من هذه الصفحة.
 */
export const CertificateApi = {
    visit(page: number): void {
        router.get(route('student.certificates'), { page }, { preserveState: true });
    },
};
