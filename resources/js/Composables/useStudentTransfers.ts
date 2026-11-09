import { computed, reactive, ref } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { useToast } from '@/Composables/useToast';
import { StudentTransferApi } from '@/Services/StudentTransferApi';
import type { ApiFailure, FormErrors, SelectOption } from '@/Types/api/common';
import type {
    StudentTransferFormPayload,
    StudentTransferIndexPageProps,
} from '@/Types/api/studentTransfer';
import type { PageProps } from '@/Types';

/** الأدوار المخوّلة بطلب النقل — مطابقة لـ StudentTransferPolicy::request. */
const REQUEST_ROLES = ['supervisor', 'org-admin', 'super-admin'];

function blankForm(): StudentTransferFormPayload {
    return { student_id: '', to_mosque_id: '', reason: '' };
}

/**
 * ViewModel لصفحة نقل الطلاب: النموذج والأخطاء والتنقّل بين الصفحات —
 * والمكوّن يعرض فقط.
 */
export function useStudentTransfers(props: StudentTransferIndexPageProps) {
    const toast = useToast();

    const canRequest = computed(() => {
        const roles = usePage<PageProps>().props.auth.roles;

        return REQUEST_ROLES.some((role) => roles.includes(role));
    });

    const studentOptions = computed<SelectOption[]>(() =>
        props.students.map((student) => ({
            value: student.id,
            label: student.user?.name ?? `طالب #${student.id}`,
        })),
    );

    const mosqueOptions = computed<SelectOption[]>(() =>
        props.mosques.map((mosque) => ({ value: mosque.id, label: mosque.name })),
    );

    const showModal = ref(false);
    const processing = ref(false);
    const errors = reactive<FormErrors>({});
    const form = reactive<StudentTransferFormPayload>(blankForm());

    function clearErrors(): void {
        Object.keys(errors).forEach((key) => delete errors[key]);
    }

    function openModal(): void {
        Object.assign(form, blankForm());
        clearErrors();
        showModal.value = true;
    }

    function closeModal(): void {
        showModal.value = false;
    }

    /**
     * أخطاء التحقق تُعرض بجوار الحقول؛ أي فشل آخر (شبكة/500) يظهر كإشعار
     * واحد برسالة الخادم بدل اختفاء صامت.
     */
    function applyFailure(error: unknown): void {
        const failure = error as ApiFailure;
        const fieldErrors = failure.errors ?? {};

        if (Object.keys(fieldErrors).length > 0) {
            Object.assign(errors, fieldErrors);
            return;
        }

        toast.error(failure.message || 'حدث خطأ غير متوقع، حاول مرة أخرى.');
    }

    async function submit(): Promise<void> {
        if (processing.value) {
            return;
        }

        clearErrors();
        processing.value = true;

        try {
            await StudentTransferApi.create({ ...form });
            closeModal();
            toast.success('تم نقل الطالب بنجاح.');
            StudentTransferApi.reload();
        } catch (error) {
            applyFailure(error);
        } finally {
            processing.value = false;
        }
    }

    function onPageChange(page: number): void {
        StudentTransferApi.visit(page);
    }

    return {
        form,
        errors,
        processing,
        canRequest,
        studentOptions,
        mosqueOptions,
        showModal,
        openModal,
        closeModal,
        submit,
        onPageChange,
    };
}
