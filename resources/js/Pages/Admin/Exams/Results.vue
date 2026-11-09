<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import DataTable, { type DataTableColumn } from '@/Components/DataTable.vue';
import Modal from '@/Components/Modal.vue';
import FormInput from '@/Components/FormInput.vue';
import FormSelect from '@/Components/FormSelect.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import { useExamSubmission } from '@/Composables/useExamSubmission';
import type { ExamResultPageModel, ExamResultsPageProps } from '@/Types/api/exam';

/**
 * العرض فقط: تسجيل/اعتماد/تصحيح النتائج كلها في useExamSubmission،
 * وكل نداء شبكة في ExamApi. لا business logic ولا axios هنا.
 */
const props = defineProps<ExamResultsPageProps>();

const {
    addForm,
    correctForm,
    sectionOptions,
    showAddModal,
    showCorrectModal,
    processing,
    errors,
    openAddModal,
    closeAddModal,
    closeCorrectModal,
    submitResult,
    approve,
    openCorrectModal,
    submitCorrection,
} = useExamSubmission(props);

const statusLabels: Record<string, string> = {
    draft: 'مسودة',
    submitted: 'مُرسلة',
    approved: 'معتمدة',
};

const columns: DataTableColumn[] = [
    { key: 'student', label: 'الطالب' },
    { key: 'score', label: 'الدرجة' },
    { key: 'grade', label: 'التقدير' },
    { key: 'status', label: 'الحالة' },
    { key: 'actions', label: 'إجراءات' },
];

// تنقّل Inertia (ترقيم الصفحات) — ليس منطق عمل، بل بناء رابط من معرف الامتحان.
function onPageChange(page: number): void {
    router.get(route('admin.exams.results', props.exam.id), { page }, { preserveState: true });
}
</script>

<template>
    <Head :title="`نتائج: ${exam.title}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h2 class="text-lg font-semibold leading-tight text-content-strong sm:text-xl">نتائج: {{ exam.title }}</h2>
                <button
                    type="button"
                    class="focus-ring inline-flex min-h-11 items-center justify-center gap-2 rounded-lg btn-primary px-4 text-sm font-semibold transition duration-150 ease-standard"
                    @click="openAddModal"
                >
                    + تسجيل نتيجة
                </button>
            </div>
        </template>

        <div class="mx-auto max-w-7xl">
            <DataTable
                :columns="columns"
                :rows="results.data"
                :current-page="results.current_page"
                :last-page="results.last_page"
                :total="results.total"
                :searchable="false"
                @update:page="onPageChange"
            >
                <template #cell-student="{ row }">
                    {{ (row as unknown as ExamResultPageModel).student?.user?.name ?? '—' }}
                </template>
                <template #cell-status="{ row }">
                    <StatusBadge :status="(row as unknown as ExamResultPageModel).status" :labels="statusLabels" />
                </template>
                <template #cell-actions="{ row }">
                    <div class="flex flex-wrap items-center gap-x-4 gap-y-2">
                        <button
                            v-if="(row as unknown as ExamResultPageModel).status !== 'approved'"
                            type="button"
                            class="text-sm font-medium text-green-600 dark:text-green-400 hover:underline"
                            @click="approve(row as unknown as ExamResultPageModel)"
                        >
                            اعتماد
                        </button>
                        <button
                            v-else
                            type="button"
                            class="text-sm font-medium text-amber-600 dark:text-amber-400 hover:underline"
                            @click="openCorrectModal(row as unknown as ExamResultPageModel)"
                        >
                            تصحيح
                        </button>
                    </div>
                </template>
            </DataTable>
        </div>

        <Modal :show="showAddModal" @close="closeAddModal">
            <form class="p-6" @submit.prevent="submitResult">
                <h3 class="mb-4 text-lg font-semibold text-content">تسجيل نتيجة جديدة</h3>
                <div class="space-y-4">
                    <FormInput id="result-student" v-model="addForm.student_id" type="number" label="رقم الطالب (Student ID)" required :error="errors.student_id" />
                    <FormSelect id="result-section" v-model="addForm.exam_section_id" label="القسم" :options="sectionOptions" required :error="errors.exam_section_id" />
                    <FormInput id="result-score" v-model="addForm.score" type="number" label="الدرجة" required :error="errors.score" />
                    <FormInput id="result-grade" v-model="addForm.grade" label="التقدير" :error="errors.grade" />
                </div>
                <div class="mt-6 flex justify-end gap-3">
                    <button type="button" class="focus-ring inline-flex min-h-11 items-center justify-center gap-2 rounded-lg btn-secondary px-4 text-sm font-medium transition duration-150 ease-standard" @click="closeAddModal">
                        إلغاء
                    </button>
                    <button type="submit" :disabled="processing" class="focus-ring inline-flex min-h-11 items-center justify-center gap-2 rounded-lg btn-primary px-4 text-sm font-semibold transition duration-150 ease-standard">
                        حفظ
                    </button>
                </div>
            </form>
        </Modal>

        <Modal :show="showCorrectModal" @close="closeCorrectModal">
            <form class="p-6" @submit.prevent="submitCorrection">
                <h3 class="mb-4 text-lg font-semibold text-content">تصحيح نتيجة معتمدة</h3>
                <div class="space-y-4">
                    <FormInput id="correct-score" v-model="correctForm.score" type="number" label="الدرجة الجديدة" required :error="errors.score" />
                    <FormInput id="correct-grade" v-model="correctForm.grade" label="التقدير الجديد" :error="errors.grade" />
                    <FormInput id="correct-reason" v-model="correctForm.reason" label="سبب التصحيح" required :error="errors.reason" />
                </div>
                <div class="mt-6 flex justify-end gap-3">
                    <button type="button" class="focus-ring inline-flex min-h-11 items-center justify-center gap-2 rounded-lg btn-secondary px-4 text-sm font-medium transition duration-150 ease-standard" @click="closeCorrectModal">
                        إلغاء
                    </button>
                    <button type="submit" :disabled="processing" class="min-h-[44px] rounded-md bg-amber-600 px-4 py-2 text-sm font-medium text-white disabled:opacity-50">
                        حفظ التصحيح
                    </button>
                </div>
            </form>
        </Modal>
    </AuthenticatedLayout>
</template>
