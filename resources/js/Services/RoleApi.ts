import { router } from '@inertiajs/vue3';

/**
 * طبقة Model/API لوحدة الأدوار والصلاحيات: المسار الوحيد الذي يعرف مسار
 * roles.update وشكل الحمولة. نفس نمط UserApi (Promise حول زيارة Inertia).
 */
export const RoleApi = {
    /** يزامن صلاحيات دور (الخادم يفرض حدود التصعيد وحماية super-admin). */
    updatePermissions(roleId: number, permissions: string[]): Promise<void> {
        return new Promise((resolve, reject) => {
            router.patch(
                route('admin.roles.update', roleId),
                { permissions },
                {
                    preserveScroll: true,
                    onSuccess: () => resolve(),
                    onError: (errors) => reject(errors),
                },
            );
        });
    },
};
