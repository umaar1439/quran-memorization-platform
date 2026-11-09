<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import DataTable, { type DataTableColumn } from '@/Components/DataTable.vue';
import Modal from '@/Components/Modal.vue';
import FormInput from '@/Components/FormInput.vue';
import FormSelect from '@/Components/FormSelect.vue';
import { useStudentTransfers } from '@/Composables/useStudentTransfers';
import type { StudentTransferIndexPageProps, StudentTransferPageModel } from '@/Types/api/studentTransfer';

/**
 * صفحة عرض فقط: النموذج والأخطاء والتنقّل في
 * useStudentTransfers/StudentTransferApi. زر النقل مرتبط بأدوار
 * StudentTransferPolicy::request (supervisor فأعلى).
 */
const props = defineProps<StudentTransferIndexPageProps>();

const {
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
} = useStudentTransfers(props);

const columns: DataTableColumn[] = [
    { key: 'student', label: 'الطالب' },
    { key: 'from_mosque', label: 'من مسجد' },
    { key: 'to_mosque', label: 'إلى مسجد' },
    { key: 'transferred_at', label: 'تاريخ النقل' },
];

/** تاريخ ISO 8601 (cast datetime) ⇒ YYYY-MM-DD للعرض. */
function shortDate(value: string): string {
    return value.slice(0, 10);
}
</script>

<template>
    <Head title="نقل الطلاب" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h2 class="text-lg font-semibold leading-tight text-content-strong sm:text-xl">سجل نقل الطلاب</h2>
                <button
                    v-if="canRequest"
                    type="button"
                    class="focus-ring inline-flex min-h-11 items-center justify-center gap-2 rounded-lg btn-primary px-4 text-sm font-semibold transition duration-150 ease-standard"
                    @click="openModal"
                >
                    + نقل طالب
                </button>
            </div>
        </template>

        <div class="mx-auto max-w-7xl">
            <DataTable
                :columns="columns"
                :rows="transfers.data"
                :current-page="transfers.current_page"
                :last-page="transfers.last_page"
                :total="transfers.total"
                :searchable="false"
                @update:page="onPageChange"
            >
                <template #cell-student="{ row }">
                    {{ (row as StudentTransferPageModel).student?.user?.name ?? '—' }}
                </template>
                <template #cell-from_mosque="{ row }">
                    {{ (row as StudentTransferPageModel).from_mosque?.name ?? '—' }}
                </template>
                <template #cell-to_mosque="{ row }">
                    {{ (row as StudentTransferPageModel).to_mosque?.name ?? '—' }}
                </template>
                <template #cell-transferred_at="{ row }">
                    {{ shortDate((row as StudentTransferPageModel).transferred_at) }}
                </template>
            </DataTable>
        </div>

        <Modal :show="showModal" @close="closeModal">
            <form class="p-6" @submit.prevent="submit">
                <h3 class="mb-4 text-lg font-semibold text-content">نقل طالب إلى مسجد آخر</h3>
                <div class="space-y-4">
                    <FormSelect
                        id="transfer-student"
                        v-model="form.student_id"
                        label="الطالب"
                        :options="studentOptions"
                        required
                        :error="errors.student_id"
                    />
                    <FormSelect
                        id="transfer-mosque"
                        v-model="form.to_mosque_id"
                        label="المسجد الجديد"
                        :options="mosqueOptions"
                        required
                        :error="errors.to_mosque_id"
                    />
                    <FormInput
                        id="transfer-reason"
                        v-model="form.reason"
                        label="سبب النقل"
                        :error="errors.reason"
                    />
                </div>
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
                        تأكيد النقل
                    </button>
                </div>
            </form>
        </Modal>
    </AuthenticatedLayout>
</template>
