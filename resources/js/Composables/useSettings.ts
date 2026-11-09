import { computed, reactive, ref } from 'vue';
import { useToast } from '@/Composables/useToast';
import { SettingApi } from '@/Services/SettingApi';
import type { FormErrors } from '@/Types/api/common';
import type {
    SettingEntryPayload,
    SettingGroup,
    SettingIndexPageProps,
    SettingPageModel,
} from '@/Types/api/setting';

const UNGROUPED_LABEL = 'عام';

/**
 * ViewModel لصفحة إعدادات المنظمة: القيم الحالية، التجميع للعرض، الحفظ
 * وأخطاء التحقق — والمكوّن يعرض فقط.
 *
 * القيم تُحفظ في خريطة مفتاح ⇒ قيمة بدل تعديل props مباشرة، فلا تتأثر
 * الحالة المرسلة من الخادم ولا يحتاج النموذج إلى نسخ مصفوفة كاملة.
 */
export function useSettings(props: SettingIndexPageProps) {
    const toast = useToast();

    const values = reactive<Record<string, string>>(
        props.settings.reduce<Record<string, string>>((accumulator, setting) => {
            accumulator[setting.key] = setting.value ?? '';

            return accumulator;
        }, {}),
    );

    /** تجميع بنود الإعدادات كما رتّبها الخادم (group ثم key). */
    const groups = computed<SettingGroup[]>(() => {
        const grouped = new Map<string, SettingPageModel[]>();

        props.settings.forEach((setting) => {
            const name = setting.group ?? UNGROUPED_LABEL;
            const bucket = grouped.get(name) ?? [];
            bucket.push(setting);
            grouped.set(name, bucket);
        });

        return Array.from(grouped.entries()).map(([name, items]) => ({ name, items }));
    });

    const processing = ref(false);
    const errors = reactive<FormErrors>({});

    /** أخطاء التحقق تصل بمفاتيح مثل `settings.0.value`؛ نعرض أولها كتنبيه. */
    const generalError = computed<string | null>(() => {
        const messages = Object.values(errors);

        return messages.length > 0 ? (messages[0] ?? null) : null;
    });

    function clearErrors(): void {
        Object.keys(errors).forEach((key) => delete errors[key]);
    }

    function updateValue(key: string, value: string): void {
        values[key] = value;
    }

    async function submit(): Promise<void> {
        if (processing.value) {
            return;
        }

        clearErrors();
        processing.value = true;

        const payload: SettingEntryPayload[] = props.settings.map((setting) => ({
            key: setting.key,
            value: values[setting.key] ?? null,
            group: setting.group,
        }));

        try {
            await SettingApi.save(payload);
            toast.success('تم حفظ الإعدادات بنجاح.');
        } catch (error) {
            Object.assign(errors, (error ?? {}) as Record<string, string>);
        } finally {
            processing.value = false;
        }
    }

    return {
        values,
        groups,
        errors,
        generalError,
        processing,
        updateValue,
        submit,
    };
}
