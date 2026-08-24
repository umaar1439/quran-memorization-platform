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
import type { Exam, ExamResult, PaginatedResponse } from '@/Types';

const props = defineProps<{
    exam: Exam;
    results: PaginatedResponse<ExamResult>;
}>();

const toast = useToast();
const api = useApi();

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

const showAddModal = ref(false);
const showCorrectModal = ref(false);
const correctingResult = ref<ExamResult | null>(null);
const processing = ref(false);
const errors = reactive<Record<string, string>>({});

const addForm = reactive({
    exam_section_id: '' as string | number,
    student_id: '' as string | number,
    score: '' as string | number,
    grade: '',
    status: 'draft',
});

const correctForm = reactive({
    score: '' as string | number,
    grade: '',
    reason: '',
});

const sectionOptions = props.exam.sections?.map((s) => ({
    value: s.id,
    label: `${s.section_type} (سورة ${s.surah_number ?? '—'})`,
})) ?? [];

function clearErrors(): void {
    Object.keys(errors).forEach((key) => delete errors[key]);
}

async function submitResult(): Promise<void> {
    processing.value = true;
    clearErrors();

    try {
        await api.post(route('exam-results.store'), { ...addForm, exam_id: props.exam.id });
        showAddModal.value = false;
        toast.success('تم تسجيل النتيجة بنجاح.');
        router.reload({ only: ['results'] });
    } catch (error) {
        const validationErrors = api.extractValidationErrors(error);
        Object.entries(validationErrors).forEach(([key, messages]) => (errors[key] = messages[0]));
        if (Object.keys(validationErrors).length === 0) toast.error(api.extractErrorMessage(error));
    } finally {
        processing.value = false;
    }
}

async function approve(result: ExamResult): Promise<void> {
    try {
        await api.post(route('exam-results.approve', result.id));
        toast.success('تم اعتماد النتيجة بنجاح.');
        router.reload({ only: ['results'] });
    } catch (error) {
        toast.error(api.extractErrorMessage(error));
    }
}

function openCorrectModal(result: ExamResult): void {
    correctingResult.value = result;
    correctForm.score = result.score;
    correctForm.grade = result.grade ?? '';
    correctForm.reason = '';
    showCorrectModal.value = true;
}

async function submitCorrection(): Promise<void> {
    if (!correctingResult.value) return;

    processing.value = true;
    clearErrors();

    try {
        await api.post(route('exam-results.correct', correctingResult.value.id), { ...correctForm });
        showCorrectModal.value = false;
        toast.success('تم تصحيح النتيجة بنجاح.');
        router.reload({ only: ['results'] });
    } catch (error) {
        const validationErrors = api.extractValidationErrors(error);
        Object.entries(validationErrors).forEach(([key, messages]) => (errors[key] = messages[0]));
        if (Object.keys(validationErrors).length === 0) toast.error(api.extractErrorMessage(error));
    } finally {
        processing.value = false;
    }
}

function onPageChange(page: number): void {
    router.get(route('admin.exams.results', props.exam.id), { page }, { preserveState: true });
}
</script>

<template>
    <Head :title="`نتائج: ${exam.title}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">نتائج: {{ exam.title }}</h2>
                <button
                    type="button"
                    class="min-h-[44px] rounded-md bg-teal-600 px-4 py-2 text-sm font-medium text-white hover:bg-teal-700"
                    @click="showAddModal = true"
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
                    {{ (row as unknown as ExamResult).student?.user?.name ?? '—' }}
                </template>
                <template #cell-status="{ row }">
                    <StatusBadge :status="(row as unknown as ExamResult).status" :labels="statusLabels" />
                </template>
                <template #cell-actions="{ row }">
                    <div class="flex gap-2">
                        <button
                            v-if="(row as unknown as ExamResult).status !== 'approved'"
                            type="button"
                            class="text-sm font-medium text-green-600 hover:underline"
                            @click="approve(row as unknown as ExamResult)"
                        >
                            اعتماد
                        </button>
                        <button
                            v-else
                            type="button"
                            class="text-sm font-medium text-amber-600 hover:underline"
                            @click="openCorrectModal(row as unknown as ExamResult)"
                        >
                            تصحيح
                        </button>
                    </div>
                </template>
            </DataTable>
        </div>

        <Modal :show="showAddModal" @close="showAddModal = false">
            <form class="p-6" @submit.prevent="submitResult">
                <h3 class="mb-4 text-lg font-semibold text-gray-900">تسجيل نتيجة جديدة</h3>
                <div class="space-y-4">
                    <FormInput id="result-student" v-model="addForm.student_id" type="number" label="رقم الطالب (Student ID)" required :error="errors.student_id" />
                    <FormSelect id="result-section" v-model="addForm.exam_section_id" label="القسم" :options="sectionOptions" required :error="errors.exam_section_id" />
                    <FormInput id="result-score" v-model="addForm.score" type="number" label="الدرجة" required :error="errors.score" />
                    <FormInput id="result-grade" v-model="addForm.grade" label="التقدير" :error="errors.grade" />
                </div>
                <div class="mt-6 flex justify-end gap-3">
                    <button type="button" class="min-h-[44px] rounded-md border border-gray-300 px-4 py-2 text-sm" @click="showAddModal = false">
                        إلغاء
                    </button>
                    <button type="submit" :disabled="processing" class="min-h-[44px] rounded-md bg-teal-600 px-4 py-2 text-sm font-medium text-white disabled:opacity-50">
                        حفظ
                    </button>
                </div>
            </form>
        </Modal>

        <Modal :show="showCorrectModal" @close="showCorrectModal = false">
            <form class="p-6" @submit.prevent="submitCorrection">
                <h3 class="mb-4 text-lg font-semibold text-gray-900">تصحيح نتيجة معتمدة</h3>
                <div class="space-y-4">
                    <FormInput id="correct-score" v-model="correctForm.score" type="number" label="الدرجة الجديدة" required :error="errors.score" />
                    <FormInput id="correct-grade" v-model="correctForm.grade" label="التقدير الجديد" :error="errors.grade" />
                    <FormInput id="correct-reason" v-model="correctForm.reason" label="سبب التصحيح" required :error="errors.reason" />
                </div>
                <div class="mt-6 flex justify-end gap-3">
                    <button type="button" class="min-h-[44px] rounded-md border border-gray-300 px-4 py-2 text-sm" @click="showCorrectModal = false">
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
