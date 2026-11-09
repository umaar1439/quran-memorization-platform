import { router } from '@inertiajs/vue3';
import type { MosqueFormPayload } from '@/Types/api/mosque';

/**
 * طبقة Model/API لوحدة المساجد: المكان الوحيد الذي يعرف مسارات admin.mosques.*
 * وشكل الحمولة. الـ ViewModel والمكوّنات لا تستدعي router مباشرة للكتابة.
 *
 * المسارات تُرجع RedirectResponse (Inertia) — فلا قيمة JSON — لذا تُغلَّف في
 * Promise: resolve عند onSuccess وreject بخريطة أخطاء التحقق عند onError،
 * فيتعامل الـ ViewModel مع النجاح والفشل بـ try/catch واحد.
 */
export const MosqueApi = {
    /** ينشئ مسجداً في منظمة المسؤول الحالي. */
    create(payload: MosqueFormPayload): Promise<void> {
        return new Promise((resolve, reject) => {
            router.post(route('admin.mosques.store'), payload, {
                preserveScroll: true,
                onSuccess: () => resolve(),
                onError: (errors) => reject(errors),
            });
        });
    },

    /** يحدّث بيانات مسجد قائم. */
    update(mosqueId: number, payload: MosqueFormPayload): Promise<void> {
        return new Promise((resolve, reject) => {
            router.patch(route('admin.mosques.update', mosqueId), payload, {
                preserveScroll: true,
                onSuccess: () => resolve(),
                onError: (errors) => reject(errors),
            });
        });
    },

    /** يحذف مسجداً — والخادم يحذف حلقاته المرتبطة في نفس المعاملة. */
    destroy(mosqueId: number): Promise<void> {
        return new Promise((resolve, reject) => {
            router.delete(route('admin.mosques.destroy', mosqueId), {
                preserveScroll: true,
                onSuccess: () => resolve(),
                onError: (errors) => reject(errors),
            });
        });
    },
};
