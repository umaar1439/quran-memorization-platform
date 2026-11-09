<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import DataTable, { type DataTableColumn } from '@/Components/DataTable.vue';
import Modal from '@/Components/Modal.vue';
import FormSelect from '@/Components/FormSelect.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import { useReports } from '@/Composables/useReports';
import type { ReportIndexPageProps, ReportPageModel } from '@/Types/api/report';

/**
 * صفحة عرض فقط: النموذج والأخطاء والتحميل والترقيم في
 * useReports/ReportApi. زر الطلب مرتبط بصلاحية reports.generate،
 * وزر التحميل بـ reports.download.
 */
const props = defineProps<ReportIndexPageProps>();

const {
    form,
    errors,
    processing,
    canGenerate,
    canDownload,
    statusLabels,
    reportTypeOptions,
    showModal,
    openModal,
    closeModal,
    submit,
    downloadReport,
    onPageChange,
} = useReports(props);

const columns: DataTableColumn[] = [
    { key: 'report_type', label: 'نوع التقرير' },
    { key: 'status', label: 'الحالة' },
    { key: 'created_at', label: 'تاريخ الطلب' },
    { key: 'actions', label: 'إجراءات' },
];
</script>

<template>
    <Head title="التقارير" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h2 class="text-lg font-semibold leading-tight text-content-strong sm:text-xl">التقارير</h2>
                <button
                    v-if="canGenerate"
                    type="button"
                    class="focus-ring inline-flex min-h-11 items-center justify-center gap-2 rounded-lg btn-primary px-4 text-sm font-semibold transition duration-150 ease-standard"
                    @click="openModal"
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
                    <StatusBadge :status="(row as ReportPageModel).status" :labels="statusLabels" />
                </template>
                <template #cell-actions="{ row }">
                    <button
                        v-if="canDownload && (row as ReportPageModel).status === 'completed'"
                        type="button"
                        class="link"
                        @click="downloadReport(row as ReportPageModel)"
                    >
                        تنزيل
                    </button>
                    <span v-else class="text-sm text-content-muted">—</span>
                </template>
            </DataTable>
        </div>

        <Modal :show="showModal" @close="closeModal">
            <form class="p-6" @submit.prevent="submit">
                <h3 class="mb-4 text-lg font-semibold text-content">طلب تقرير جديد</h3>
                <FormSelect
                    id="report-type"
                    v-model="form.report_type"
                    label="نوع التقرير"
                    :options="reportTypeOptions"
                    required
                    :error="errors.report_type"
                />
                <div class="mt-6 flex justify-end gap-3">
                    <button
                        type="button"
                        class="focus-ring inline-flex min-h-11 items-center justify-center gap-2 rounded-lg btn-secondary px-4 text-sm font-medium transition duration-150 ease-standard"
                        @click="closeModal"
                    >
                        إلغاء
                    </button>
                    <button
                        type="submit"
                        :disabled="processing"
                        class="focus-ring inline-flex min-h-11 items-center justify-center gap-2 rounded-lg btn-primary px-4 text-sm font-semibold transition duration-150 ease-standard"
                    >
                        إرسال الطلب
                    </button>
                </div>
            </form>
        </Modal>
    </AuthenticatedLayout>
</template>
