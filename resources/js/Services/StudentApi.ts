import { router } from '@inertiajs/vue3';
import type { StudentFilters, StudentFormPayload } from '@/Types/api/student';

/**
 * طبقة Model/API لوحدة الطلاب: المكان الوحيد الذي يعرف مسارات
 * admin.students.* وشكل الحمولة. الـ ViewModel والمكوّنات لا تستدعي router
 * مباشرة — لا للكتابة ولا للتنقل بين الصفحات المفلترة.
 *
 * مسارات الكتابة تُرجع RedirectResponse (Inertia)، فتُغلَّف في Promise:
 * resolve عند onSuccess وreject بخريطة أخطاء التحقق عند onError، فيتعامل
 * الـ ViewModel مع النجاح والفشل بـ try/catch واحد (نمط MosqueApi/CircleApi).
 */
export const StudentApi = {
    /** ينشئ طالباً (حساب + ملف) في منظمة المسؤول الحالي. */
    create(payload: StudentFormPayload): Promise<void> {
        return new Promise((resolve, reject) => {
            router.post(route('admin.students.store'), payload, {
                preserveScroll: true,
                onSuccess: () => resolve(),
                onError: (errors) => reject(errors),
            });
        });
    },

    /** يحدّث بيانات الطالب؛ كلمة المرور الفارغة تعني «بلا تغيير». */
    update(studentId: number, payload: StudentFormPayload): Promise<void> {
        return new Promise((resolve, reject) => {
            router.patch(route('admin.students.update', studentId), payload, {
                preserveScroll: true,
                onSuccess: () => resolve(),
                onError: (errors) => reject(errors),
            });
        });
    },

    /** يحذف طالباً (Soft Delete على الخادم) مع حسابه. */
    destroy(studentId: number): Promise<void> {
        return new Promise((resolve, reject) => {
            router.delete(route('admin.students.destroy', studentId), {
                preserveScroll: true,
                onSuccess: () => resolve(),
                onError: (errors) => reject(errors),
            });
        });
    },

    /**
     * تنقّل Inertia للقائمة (بحث/فلترة/ترقيم). قراءة فقط: يبني رابطاً
     * بالمعاملات الحالية ويترك الحالة كما هي.
     */
    visit(query: StudentFilters & { page?: number }, replace = false): void {
        router.get(route('admin.students.index'), query, { preserveState: true, replace });
    },
};
