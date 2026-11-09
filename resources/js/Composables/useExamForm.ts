import { reactive, ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { useApi } from '@/Composables/useApi';
import { useToast } from '@/Composables/useToast';
import { ExamApi } from '@/Services/ExamApi';
import type { ExamCreatePayload, ExamIndexPageProps } from '@/Types/api/exam';

/**
 * ViewModel لصفحة قائمة الامتحانات (Admin/Exams/Index):
 * حالة نموذج الإنشاء، خيارات المسجد/الحلقة، الإرسال، توزيع أخطاء التحقق
 * على الحقول، والأخطاء غير المتوقعة عبر Toast. المكوّن يعرض فقط.
 */
export function useExamForm(props: Pick<ExamIndexPageProps, 'mosques' | 'circles'>) {
    const api = useApi();
    const toast = useToast();

    const mosqueOptions = props.mosques.map((mosque) => ({ value: mosque.id, label: mosque.name }));
    const circleOptions = props.circles.map((circle) => ({ value: circle.id, label: circle.name }));

    const showModal = ref(false);
    const processing = ref(false);
    const errors = reactive<Record<string, string>>({});

    // القيم الأولية '' بدل null: ConvertEmptyStringsToNull في الخادم حوّل هذا
    // السلوك سابقاً، ونتائج التحقق (nullable|integer) تبقى نفسها.
    const form = reactive<ExamCreatePayload>({
        mosque_id: '',
        circle_id: '',
        title: '',
        exam_type: 'monthly',
        scheduled_at: '',
    });

    function clearErrors(): void {
        Object.keys(errors).forEach((key) => delete errors[key]);
    }

    function resetForm(): void {
        form.mosque_id = '';
        form.circle_id = '';
        form.title = '';
        form.exam_type = 'monthly';
        form.scheduled_at = '';
        clearErrors();
    }

    function openModal(): void {
        clearErrors();
        showModal.value = true;
    }

    function closeModal(): void {
        showModal.value = false;
    }

    async function submit(): Promise<void> {
        processing.value = true;
        clearErrors();

        try {
            await ExamApi.create({ ...form });
            showModal.value = false;
            resetForm();
            toast.success('تم إنشاء الامتحان بنجاح.');
            router.reload({ only: ['exams'] });
        } catch (error) {
            const validationErrors = api.extractValidationErrors(error);

            Object.entries(validationErrors).forEach(([key, messages]) => {
                errors[key] = messages[0];
            });

            if (Object.keys(validationErrors).length === 0) {
                toast.error(api.extractErrorMessage(error));
            }
        } finally {
            processing.value = false;
        }
    }

    return {
        form,
        mosqueOptions,
        circleOptions,
        showModal,
        processing,
        errors,
        submit,
        resetForm,
        openModal,
        closeModal,
    };
}
