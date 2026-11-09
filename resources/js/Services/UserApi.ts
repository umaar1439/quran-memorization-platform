import { router } from '@inertiajs/vue3';
import type { UserFormPayload } from '@/Types/api/user';

/**
 * طبقة Model/API لوحدة المستخدمين: المكان الوحيد الذي يعرف مسارات users.*
 * وشكل الحمولة. الـ ViewModel والمكوّنات لا تستدعي router مباشرة للكتابة.
 *
 * المسارات تُرجع RedirectResponse (Inertia) — فلا قيمة JSON — لذا تُغلَّف في
 * Promise: resolve عند onSuccess وreject بخريطة أخطاء التحقق عند onError،
 * فيتعامل الـ ViewModel مع النجاح والفشل بـ try/catch واحد.
 */
export const UserApi = {
    /** ينشئ مستخدماً في منظمة المسؤول الحالي بالدور المختار. */
    create(payload: UserFormPayload): Promise<void> {
        return new Promise((resolve, reject) => {
            router.post(route('admin.users.store'), payload, {
                preserveScroll: true,
                onSuccess: () => resolve(),
                onError: (errors) => reject(errors),
            });
        });
    },

    /** يحدّث بيانات مستخدم؛ كلمة المرور الفارغة تعني "لا تغيير". */
    update(userId: number, payload: UserFormPayload): Promise<void> {
        return new Promise((resolve, reject) => {
            router.patch(route('admin.users.update', userId), payload, {
                preserveScroll: true,
                onSuccess: () => resolve(),
                onError: (errors) => reject(errors),
            });
        });
    },

    /** يحذف مستخدماً (Soft Delete على الخادم). */
    destroy(userId: number): Promise<void> {
        return new Promise((resolve, reject) => {
            router.delete(route('admin.users.destroy', userId), {
                preserveScroll: true,
                onSuccess: () => resolve(),
                onError: (errors) => reject(errors),
            });
        });
    },
};
