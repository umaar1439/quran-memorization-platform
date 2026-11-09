import { router } from '@inertiajs/vue3';
import type { FeatureFlagTogglePayload } from '@/Types/api/featureFlag';

/**
 * طبقة Model/API لأعلام الميزات: المكان الوحيد الذي يعرف مسار التبديل.
 * الاستجابة RedirectResponse (Inertia)، فتُغلَّف في Promise كما في بقية
 * وحدات الكتابة (TeacherApi/StudentApi/SettingApi).
 */
export const FeatureFlagApi = {
    /** يبدّل علم ميزة واحد ويُبطل ذاكرته على الخادم. */
    toggle(flagId: number, payload: FeatureFlagTogglePayload): Promise<void> {
        return new Promise((resolve, reject) => {
            router.patch(route('admin.feature-flags.update', flagId), payload, {
                preserveScroll: true,
                onSuccess: () => resolve(),
                onError: (errors) => reject(errors),
            });
        });
    },
};
