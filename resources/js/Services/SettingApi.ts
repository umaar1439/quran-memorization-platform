import { router } from '@inertiajs/vue3';
import type { SettingEntryPayload } from '@/Types/api/setting';

/**
 * طبقة Model/API لوحدة الإعدادات: المكان الوحيد الذي يعرف مسار الحفظ وشكل
 * الحمولة. الحفظ يُرجع RedirectResponse (Inertia)، فتُغلَّف في Promise:
 * resolve عند onSuccess وreject بخريطة أخطاء التحقق عند onError.
 */
export const SettingApi = {
    /** يحفظ كل الإعدادات دفعة واحدة (upsert جماعي على الخادم). */
    save(entries: SettingEntryPayload[]): Promise<void> {
        return new Promise((resolve, reject) => {
            router.patch(
                route('admin.settings.update'),
                { settings: entries },
                {
                    preserveScroll: true,
                    onSuccess: () => resolve(),
                    onError: (errors) => reject(errors),
                },
            );
        });
    },
};
