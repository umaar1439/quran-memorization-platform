<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import DataTable, { type DataTableColumn } from '@/Components/DataTable.vue';
import Modal from '@/Components/Modal.vue';
import FormInput from '@/Components/FormInput.vue';
import FormSelect from '@/Components/FormSelect.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import { useExamForm } from '@/Composables/useExamForm';
import type { ExamIndexPageProps, ExamPageModel } from '@/Types/api/exam';

/**
 * العرض فقط: كل حالة النموذج/الإرسال/الأخطاء/التنبيهات في useExamForm،
 * وكل نداء شبكة في ExamApi. لا business logic ولا axios هنا.
 */
const props = defineProps<ExamIndexPageProps>();

const { form, mosqueOptions, circleOptions, showModal, processing, errors, submit, openModal, closeModal } =
    useExamForm(props);

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

// تنقّل Inertia (ترقيم الصفحات) — ليس منطق عمل، بل بناء رابط من الفلاتر الحالية.
function onPageChange(page: number): void {
    router.get(route('admin.exams.page'), { ...props.filters, page }, { preserveState: true });
}
</script>

<template>
    <Head title="الامتحانات" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h2 class="text-lg font-semibold leading-tight text-content-strong sm:text-xl">الامتحانات</h2>
                <button
                    type="button"
                    class="focus-ring inline-flex min-h-11 items-center justify-center gap-2 rounded-lg btn-primary px-4 text-sm font-semibold transition duration-150 ease-standard"
                    @click="openModal"
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
                    {{ (row as unknown as ExamPageModel).circle?.name ?? '—' }}
                </template>
                <template #cell-scheduled_at="{ row }">
                    {{ (row as unknown as ExamPageModel).scheduled_at ?? '—' }}
                </template>
                <template #cell-status="{ row }">
                    <StatusBadge :status="(row as unknown as ExamPageModel).status" :labels="statusLabels" />
                </template>
                <template #cell-actions="{ row }">
                    <Link :href="route('admin.exams.results', (row as unknown as ExamPageModel).id)" class="link">
                        عرض النتائج
                    </Link>
                </template>
            </DataTable>
        </div>

        <Modal :show="showModal" @close="closeModal">
            <form class="p-6" @submit.prevent="submit">
                <h3 class="mb-4 text-lg font-semibold text-content">إنشاء امتحان جديد</h3>
                <div class="space-y-4">
                    <FormInput id="exam-title" v-model="form.title" label="عنوان الامتحان" required :error="errors.title" />
                    <FormSelect id="exam-mosque" v-model="form.mosque_id" label="المسجد" :options="mosqueOptions" :error="errors.mosque_id" />
                    <FormSelect id="exam-circle" v-model="form.circle_id" label="الحلقة" :options="circleOptions" :error="errors.circle_id" />
                    <FormInput id="exam-type" v-model="form.exam_type" label="نوع الامتحان" required :error="errors.exam_type" />
                    <FormInput id="exam-scheduled" v-model="form.scheduled_at" type="datetime-local" label="تاريخ الجدولة" :error="errors.scheduled_at" />
                </div>
                <div class="mt-6 flex justify-end gap-3">
                    <button type="button" class="focus-ring inline-flex min-h-11 items-center justify-center gap-2 rounded-lg btn-secondary px-4 text-sm font-medium transition duration-150 ease-standard" @click="closeModal">
                        إلغاء
                    </button>
                    <button
                        type="submit"
                        :disabled="processing"
                        class="focus-ring inline-flex min-h-11 items-center justify-center gap-2 rounded-lg btn-primary px-4 text-sm font-semibold transition duration-150 ease-standard"
                    >
                        حفظ
                    </button>
                </div>
            </form>
        </Modal>
    </AuthenticatedLayout>
</template>
