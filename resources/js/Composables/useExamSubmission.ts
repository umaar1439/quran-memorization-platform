import { computed, reactive, ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { useApi } from '@/Composables/useApi';
import { useToast } from '@/Composables/useToast';
import { ExamApi } from '@/Services/ExamApi';
import type {
    ExamCorrectionPayload,
    ExamResultCreatePayload,
    ExamResultPageModel,
    ExamResultsPageProps,
} from '@/Types/api/exam';

/**
 * ViewModel لصفحة نتائج امتحان (Admin/Exams/Results): تسجيل نتيجة، اعتماد
 * نتيجة، وتصحيح نتيجة معتمدة (Correction Workflow) — بما في ذلك حالة كل
 * نموذج، توزيع أخطاء التحقق، والرسائل (Toast). المكوّن يعرض فقط.
 *
 * لا يوجد مؤقّت/عدّاد زمني في هذه الوحدة: لا شاشة أداء امتحان للطالب ولا
 * عمود `duration` في جدول exams، فإضافته ستكون ميزة جديدة لا إعادة هيكلة.
 */
export function useExamSubmission(props: Pick<ExamResultsPageProps, 'exam'>) {
    const api = useApi();
    const toast = useToast();

    const showAddModal = ref(false);
    const showCorrectModal = ref(false);
    const correctingResult = ref<ExamResultPageModel | null>(null);
    const processing = ref(false);
    const errors = reactive<Record<string, string>>({});

    const addForm = reactive<Omit<ExamResultCreatePayload, 'exam_id'>>({
        exam_section_id: '',
        student_id: '',
        score: '',
        grade: '',
        status: 'draft',
    });

    const correctForm = reactive<ExamCorrectionPayload>({
        score: '',
        grade: '',
        reason: '',
    });

    const sectionOptions = computed(() =>
        (props.exam.sections ?? []).map((section) => ({
            value: section.id,
            label: `${section.section_type} (سورة ${section.surah_number ?? '—'})`,
        })),
    );

    function clearErrors(): void {
        Object.keys(errors).forEach((key) => delete errors[key]);
    }

    function handleFailure(error: unknown): void {
        const validationErrors = api.extractValidationErrors(error);

        Object.entries(validationErrors).forEach(([key, messages]) => {
            errors[key] = messages[0];
        });

        if (Object.keys(validationErrors).length === 0) {
            toast.error(api.extractErrorMessage(error));
        }
    }

    function openAddModal(): void {
        clearErrors();
        showAddModal.value = true;
    }

    function closeAddModal(): void {
        showAddModal.value = false;
    }

    function closeCorrectModal(): void {
        showCorrectModal.value = false;
    }

    async function submitResult(): Promise<void> {
        processing.value = true;
        clearErrors();

        try {
            await ExamApi.recordResult({ ...addForm, exam_id: props.exam.id });
            showAddModal.value = false;
            toast.success('تم تسجيل النتيجة بنجاح.');
            router.reload({ only: ['results'] });
        } catch (error) {
            handleFailure(error);
        } finally {
            processing.value = false;
        }
    }

    async function approve(result: ExamResultPageModel): Promise<void> {
        try {
            await ExamApi.approveResult(result.id);
            toast.success('تم اعتماد النتيجة بنجاح.');
            router.reload({ only: ['results'] });
        } catch (error) {
            toast.error(api.extractErrorMessage(error));
        }
    }

    function openCorrectModal(result: ExamResultPageModel): void {
        correctingResult.value = result;
        // score عمود nullable في قاعدة البيانات: لا نفترض وجود قيمة.
        correctForm.score = result.score ?? '';
        correctForm.grade = result.grade ?? '';
        correctForm.reason = '';
        clearErrors();
        showCorrectModal.value = true;
    }

    async function submitCorrection(): Promise<void> {
        const target = correctingResult.value;

        if (!target) {
            return;
        }

        processing.value = true;
        clearErrors();

        try {
            await ExamApi.correctResult(target.id, { ...correctForm });
            showCorrectModal.value = false;
            toast.success('تم تصحيح النتيجة بنجاح.');
            router.reload({ only: ['results'] });
        } catch (error) {
            handleFailure(error);
        } finally {
            processing.value = false;
        }
    }

    return {
        addForm,
        correctForm,
        sectionOptions,
        showAddModal,
        showCorrectModal,
        correctingResult,
        processing,
        errors,
        openAddModal,
        closeAddModal,
        closeCorrectModal,
        submitResult,
        approve,
        openCorrectModal,
        submitCorrection,
    };
}
