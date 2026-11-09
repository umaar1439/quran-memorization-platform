import { ref } from 'vue';
import { useToast } from '@/Composables/useToast';
import { FeatureFlagApi } from '@/Services/FeatureFlagApi';
import type { FeatureFlagIndexPageProps, FeatureFlagPageModel } from '@/Types/api/featureFlag';

/**
 * ViewModel لصفحة أعلام الميزات: التبديل مع تعطيل المفتاح الذي يُحفظ الآن
 * (منع نقرات متتالية تُنتج تغييرات متضاربة) — والمكوّن يعرض فقط.
 *
 * لا نحتفظ بنسخة محلية من `flags`: الخادم هو مصدر الحقيقة، وInertia يعيد
 * تحميل الخصائص بعد الحفظ.
 */
export function useFeatureFlags(props: FeatureFlagIndexPageProps) {
    const toast = useToast();

    const pendingId = ref<number | null>(null);

    function isPending(flag: FeatureFlagPageModel): boolean {
        return pendingId.value === flag.id;
    }

    async function toggle(flag: FeatureFlagPageModel): Promise<void> {
        if (pendingId.value !== null) {
            return;
        }

        pendingId.value = flag.id;

        try {
            await FeatureFlagApi.toggle(flag.id, { enabled: !flag.enabled });
            toast.success('تم تحديث حالة الميزة بنجاح.');
        } catch {
            // أخطاء التحقق/الشبكة تُعرض عبر معالج Inertia العام؛ نكتفي
            // بإعادة تفعيل المفتاح حتى لا يبقى معطلاً بلا سبب ظاهر.
        } finally {
            pendingId.value = null;
        }
    }

    return {
        flags: props.flags,
        toggle,
        isPending,
    };
}
