import { computed, reactive, ref } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { useToast } from '@/Composables/useToast';
import { ReportApi } from '@/Services/ReportApi';
import type { ApiFailure, FormErrors, SelectOption } from '@/Types/api/common';
import type { ReportFormPayload, ReportIndexPageProps, ReportPageModel } from '@/Types/api/report';
import type { PageProps } from '@/Types';

/** أنواع التقارير المتاحة للطلب — نصوص واجهة فقط (الخادم يتحقق من النوع). */
const REPORT_TYPE_OPTIONS: SelectOption[] = [
    { value: 'students_summary', label: 'ملخص الطلاب' },
    { value: 'recitations_summary', label: 'ملخص التسميعات' },
    { value: 'attendance_summary', label: 'ملخص الحضور' },
];

/** تسميات عربية لحالات ReportStatus. */
const STATUS_LABELS: Record<string, string> = {
    queued: 'في الانتظار',
    processing: 'قيد المعالجة',
    completed: 'مكتمل',
    failed: 'فشل',
};

function blankForm(): ReportFormPayload {
    return { report_type: '' };
}

/**
 * ViewModel لصفحة التقارير: طلب تقرير جديد، توزيع أخطاء التحقق، التحميل،
 * والترقيم — والمكوّن يعرض فقط.
 */
export function useReports(props: ReportIndexPageProps) {
    const toast = useToast();

    const canGenerate = computed(() =>
        usePage<PageProps>().props.auth.permissions.includes('reports.generate'),
    );
    const canDownload = computed(() =>
        usePage<PageProps>().props.auth.permissions.includes('reports.download'),
    );

    const showModal = ref(false);
    const processing = ref(false);
    const errors = reactive<FormErrors>({});
    const form = reactive<ReportFormPayload>(blankForm());

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
            await ReportApi.create({ ...form });
            closeModal();
            toast.success('تم إرسال طلب التقرير، سيظهر عند اكتماله.');
            ReportApi.reload();
        } catch (error) {
            applyFailure(error);
        } finally {
            processing.value = false;
        }
    }

    function downloadReport(report: ReportPageModel): void {
        window.open(ReportApi.downloadUrl(report.id), '_blank');
    }

    function onPageChange(page: number): void {
        ReportApi.visit(page);
    }

    return {
        form,
        errors,
        processing,
        canGenerate,
        canDownload,
        statusLabels: STATUS_LABELS,
        reportTypeOptions: REPORT_TYPE_OPTIONS,
        showModal,
        openModal,
        closeModal,
        submit,
        downloadReport,
        onPageChange,
    };
}
