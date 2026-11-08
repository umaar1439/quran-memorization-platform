<script setup lang="ts">
import { reactive, ref } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import DataTable, { type DataTableColumn } from '@/Components/DataTable.vue';
import Modal from '@/Components/Modal.vue';
import FormInput from '@/Components/FormInput.vue';
import FormSelect from '@/Components/FormSelect.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import { useToast } from '@/Composables/useToast';
import { useApi } from '@/Composables/useApi';
import type { PaginatedResponse, Report } from '@/Types';

const props = defineProps<{
    reports: PaginatedResponse<Report>;
}>();

const toast = useToast();
const api = useApi();

const statusLabels: Record<string, string> = {
    pending: 'قيد المعالجة',
    processing: 'قيد المعالجة',
    completed: 'مكتمل',
    failed: 'فشل',
};

const reportTypeOptions = [
    { value: 'students_summary', label: 'ملخص الطلاب' },
    { value: 'recitations_summary', label: 'ملخص التسميعات' },
    { value: 'attendance_summary', label: 'ملخص الحضور' },
];

const columns: DataTableColumn[] = [
    { key: 'report_type', label: 'نوع التقرير' },
    { key: 'status', label: 'الحالة' },
    { key: 'created_at', label: 'تاريخ الطلب' },
    { key: 'actions', label: 'إجراءات' },
];

const showModal = ref(false);
const processing = ref(false);
const errors = reactive<Record<string, string>>({});

const form = reactive({
    report_type: '',
});

async function submit(): Promise<void> {
    processing.value = true;
    Object.keys(errors).forEach((key) => delete errors[key]);

    try {
        await api.post(route('reports.store'), { ...form });
        showModal.value = false;
        toast.success('تم إرسال طلب التقرير، سيظهر عند اكتماله.');
        router.reload({ only: ['reports'] });
    } catch (error) {
        const validationErrors = api.extractValidationErrors(error);
        Object.entries(validationErrors).forEach(([key, messages]) => (errors[key] = messages[0]));
        if (Object.keys(validationErrors).length === 0) toast.error(api.extractErrorMessage(error));
    } finally {
        processing.value = false;
    }
}

function downloadReport(report: Report): void {
    window.open(route('reports.download', report.id), '_blank');
}

function onPageChange(page: number): void {
    router.get(route('admin.reports.page'), { page }, { preserveState: true });
}
</script>

<template>
    <Head title="التقارير" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-content-strong">التقارير</h2>
                <button
                    type="button"
                    class="min-h-[44px] rounded-md bg-teal-600 px-4 py-2 text-sm font-medium text-white hover:bg-teal-700"
                    @click="showModal = true"
                >
                    + طلب تقرير جديد
                </button>
            </div>
        </template>

        <div class="mx-auto max-w-7xl">
            <DataTable
                :columns="columns"
                :rows="reports.data"
                :current-page="reports.current_page"
                :last-page="reports.last_page"
                :total="reports.total"
                :searchable="false"
                @update:page="onPageChange"
            >
                <template #cell-status="{ row }">
                    <StatusBadge :status="(row as unknown as Report).status" :labels="statusLabels" />
                </template>
                <template #cell-actions="{ row }">
                    <button
                        v-if="(row as unknown as Report).status === 'completed'"
                        type="button"
                        class="text-sm font-medium text-teal-600 dark:text-teal-400 hover:underline"
                        @click="downloadReport(row as unknown as Report)"
                    >
                        تنزيل
                    </button>
                </template>
            </DataTable>
        </div>

        <Modal :show="showModal" @close="showModal = false">
            <form class="p-6" @submit.prevent="submit">
                <h3 class="mb-4 text-lg font-semibold text-content">طلب تقرير جديد</h3>
                <FormSelect id="report-type" v-model="form.report_type" label="نوع التقرير" :options="reportTypeOptions" required :error="errors.report_type" />
                <div class="mt-6 flex justify-end gap-3">
                    <button type="button" class="min-h-[44px] rounded-md border border-edge-strong px-4 py-2 text-sm" @click="showModal = false">
                        إلغاء
                    </button>
                    <button type="submit" :disabled="processing" class="min-h-[44px] rounded-md bg-teal-600 px-4 py-2 text-sm font-medium text-white disabled:opacity-50">
                        إرسال الطلب
                    </button>
                </div>
            </form>
        </Modal>
    </AuthenticatedLayout>
</template>
