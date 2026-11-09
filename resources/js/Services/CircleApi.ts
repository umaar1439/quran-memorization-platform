import { router } from '@inertiajs/vue3';
import type { CircleFormPayload } from '@/Types/api/circle';

/**
 * طبقة Model/API لوحدة الحلقات: المكان الوحيد الذي يعرف مسارات admin.circles.*
 * وشكل الحمولة. الـ ViewModel والمكوّنات لا تستدعي router مباشرة للكتابة.
 *
 * نفس نمط UserApi/MosqueApi: المسارات تُرجع RedirectResponse، فتُغلَّف في
 * Promise (resolve عند onSuccess، reject بخريطة أخطاء التحقق عند onError).
 */
export const CircleApi = {
    /** ينشئ حلقة داخل منظمة المسؤول الحالي. */
    create(payload: CircleFormPayload): Promise<void> {
        return new Promise((resolve, reject) => {
            router.post(route('admin.circles.store'), payload, {
                preserveScroll: true,
                onSuccess: () => resolve(),
                onError: (errors) => reject(errors),
            });
        });
    },

    /** يحدّث بيانات حلقة قائمة. */
    update(circleId: number, payload: CircleFormPayload): Promise<void> {
        return new Promise((resolve, reject) => {
            router.patch(route('admin.circles.update', circleId), payload, {
                preserveScroll: true,
                onSuccess: () => resolve(),
                onError: (errors) => reject(errors),
            });
        });
    },

    /** يحذف حلقة (Soft Delete على الخادم). */
    destroy(circleId: number): Promise<void> {
        return new Promise((resolve, reject) => {
            router.delete(route('admin.circles.destroy', circleId), {
                preserveScroll: true,
                onSuccess: () => resolve(),
                onError: (errors) => reject(errors),
            });
        });
    },
};
