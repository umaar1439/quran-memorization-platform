import { router } from '@inertiajs/vue3';
import type { TeacherFilters, TeacherFormPayload } from '@/Types/api/teacher';

/**
 * طبقة Model/API لوحدة المعلمين: المكان الوحيد الذي يعرف مسارات
 * admin.teachers.* وشكل الحمولة. الـ ViewModel والمكوّنات لا تستدعي router
 * مباشرة — لا للكتابة ولا للتنقل بين الصفحات المفلترة.
 *
 * مسارات الكتابة تُرجع RedirectResponse (Inertia)، فتُغلَّف في Promise:
 * resolve عند onSuccess وreject بخريطة أخطاء التحقق عند onError، فيتعامل
 * الـ ViewModel مع النجاح والفشل بـ try/catch واحد (نمط StudentApi/CircleApi).
 */
export const TeacherApi = {
    /** ينشئ معلماً (حساب + ملف + ربط حلقات) في منظمة المسؤول الحالي. */
    create(payload: TeacherFormPayload): Promise<void> {
        return new Promise((resolve, reject) => {
            router.post(route('admin.teachers.store'), payload, {
                preserveScroll: true,
                onSuccess: () => resolve(),
                onError: (errors) => reject(errors),
            });
        });
    },

    /** يحدّث بيانات المعلم؛ كلمة المرور الفارغة تعني «بلا تغيير». */
    update(teacherId: number, payload: TeacherFormPayload): Promise<void> {
        return new Promise((resolve, reject) => {
            router.patch(route('admin.teachers.update', teacherId), payload, {
                preserveScroll: true,
                onSuccess: () => resolve(),
                onError: (errors) => reject(errors),
            });
        });
    },

    /** يحذف معلماً مع حسابه ويلغي ربطه بكل الحلقات. */
    destroy(teacherId: number): Promise<void> {
        return new Promise((resolve, reject) => {
            router.delete(route('admin.teachers.destroy', teacherId), {
                preserveScroll: true,
                onSuccess: () => resolve(),
                onError: (errors) => reject(errors),
            });
        });
    },

    /**
     * تنقّل Inertia للقائمة (بحث/ترقيم). قراءة فقط: يبني رابطاً
     * بالمعاملات الحالية ويترك الحالة كما هي.
     */
    visit(query: TeacherFilters & { page?: number }, replace = false): void {
        router.get(route('admin.teachers.index'), query, { preserveState: true, replace });
    },
};
