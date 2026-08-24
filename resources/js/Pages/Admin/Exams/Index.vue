<script setup lang="ts">
import { reactive, ref } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import DataTable, { type DataTableColumn } from '@/Components/DataTable.vue';
import Modal from '@/Components/Modal.vue';
import FormInput from '@/Components/FormInput.vue';
import FormSelect from '@/Components/FormSelect.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import { useToast } from '@/Composables/useToast';
import { useApi } from '@/Composables/useApi';
import type { Circle, Exam, Mosque, PaginatedResponse } from '@/Types';

const props = defineProps<{
    exams: PaginatedResponse<Exam>;
    mosques: Pick<Mosque, 'id' | 'name'>[];
    circles: Pick<Circle, 'id' | 'name' | 'mosque_id'>[];
    filters: { mosque_id?: string; circle_id?: string };
}>();

const toast = useToast();
const api = useApi();

const statusLabels: Record<string, string> = {
    draft: 'مسودة',
    scheduled: 'مجدول',
    in_progress: 'قيد التنفيذ',
    completed: 'مكتمل',
    approved: 'معتمد',
};

const columns: DataTableColumn[] = [
    { key: 'title', label: 'عنوان الامتحان' },
    { key: 'circle', label: 'الحلقة' },
    { key: 'scheduled_at', label: 'تاريخ الجدولة' },
    { key: 'status', label: 'الحالة' },
    { key: 'actions', label: 'إجراءات' },
];

const mosqueOptions = props.mosques.map((m) => ({ value: m.id, label: m.name }));
const circleOptions = props.circles.map((c) => ({ value: c.id, label: c.name }));

const showModal = ref(false);
const processing = ref(false);
const errors = reactive<Record<string, string>>({});

const form = reactive({
    mosque_id: '' as string | number,
    circle_id: '' as string | number,
    title: '',
    exam_type: 'monthly',
    scheduled_at: '',
});

function resetForm(): void {
    form.mosque_id = '';
    form.circle_id = '';
    form.title = '';
    form.exam_type = 'monthly';
    form.scheduled_at = '';
    Object.keys(errors).forEach((key) => delete errors[key]);
}

async function submit(): Promise<void> {
    processing.value = true;
    Object.keys(errors).forEach((key) => delete errors[key]);

    try {
        await api.post(route('exams.store'), { ...form });
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

function onPageChange(page: number): void {
    router.get(route('admin.exams.page'), { ...props.filters, page }, { preserveState: true });
}
</script>

<template>
    <Head title="الامتحانات" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">الامتحانات</h2>
                <button
                    type="button"
                    class="min-h-[44px] rounded-md bg-teal-600 px-4 py-2 text-sm font-medium text-white hover:bg-teal-700"
                    @click="showModal = true"
                >
                    + امتحان جديد
                </button>
            </div>
        </template>

        <div class="mx-auto max-w-7xl">
            <DataTable
                :columns="columns"
                :rows="exams.data"
                :current-page="exams.current_page"
                :last-page="exams.last_page"
                :total="exams.total"
                :searchable="false"
                @update:page="onPageChange"
            >
                <template #cell-circle="{ row }">
                    {{ (row as unknown as Exam).circle?.name ?? '—' }}
                </template>
                <template #cell-scheduled_at="{ row }">
                    {{ (row as unknown as Exam).scheduled_at ?? '—' }}
                </template>
                <template #cell-status="{ row }">
                    <StatusBadge :status="(row as unknown as Exam).status" :labels="statusLabels" />
                </template>
                <template #cell-actions="{ row }">
                    <Link :href="route('admin.exams.results', (row as unknown as Exam).id)" class="text-sm font-medium text-teal-600 hover:underline">
                        عرض النتائج
                    </Link>
                </template>
            </DataTable>
        </div>

        <Modal :show="showModal" @close="showModal = false">
            <form class="p-6" @submit.prevent="submit">
                <h3 class="mb-4 text-lg font-semibold text-gray-900">إنشاء امتحان جديد</h3>
                <div class="space-y-4">
                    <FormInput id="exam-title" v-model="form.title" label="عنوان الامتحان" required :error="errors.title" />
                    <FormSelect id="exam-mosque" v-model="form.mosque_id" label="المسجد" :options="mosqueOptions" :error="errors.mosque_id" />
                    <FormSelect id="exam-circle" v-model="form.circle_id" label="الحلقة" :options="circleOptions" :error="errors.circle_id" />
                    <FormInput id="exam-type" v-model="form.exam_type" label="نوع الامتحان" required :error="errors.exam_type" />
                    <FormInput id="exam-scheduled" v-model="form.scheduled_at" type="datetime-local" label="تاريخ الجدولة" :error="errors.scheduled_at" />
                </div>
                <div class="mt-6 flex justify-end gap-3">
                    <button type="button" class="min-h-[44px] rounded-md border border-gray-300 px-4 py-2 text-sm" @click="showModal = false">
                        إلغاء
                    </button>
                    <button
                        type="submit"
                        :disabled="processing"
                        class="min-h-[44px] rounded-md bg-teal-600 px-4 py-2 text-sm font-medium text-white disabled:opacity-50"
                    >
                        حفظ
                    </button>
                </div>
            </form>
        </Modal>
    </AuthenticatedLayout>
</template>
