<script setup lang="ts">
import { reactive, ref } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import DataTable, { type DataTableColumn } from '@/Components/DataTable.vue';
import Modal from '@/Components/Modal.vue';
import FormInput from '@/Components/FormInput.vue';
import FormSelect from '@/Components/FormSelect.vue';
import { useToast } from '@/Composables/useToast';
import { useApi } from '@/Composables/useApi';
import type { Mosque, PaginatedResponse, StudentProfile, StudentTransfer } from '@/Types';

const props = defineProps<{
    transfers: PaginatedResponse<StudentTransfer>;
    mosques: Pick<Mosque, 'id' | 'name'>[];
    students: (Pick<StudentProfile, 'id' | 'user_id' | 'mosque_id' | 'circle_id'> & { user?: { name: string } })[];
}>();

const toast = useToast();
const api = useApi();

const columns: DataTableColumn[] = [
    { key: 'student', label: 'الطالب' },
    { key: 'from_mosque', label: 'من مسجد' },
    { key: 'to_mosque', label: 'إلى مسجد' },
    { key: 'transferred_at', label: 'تاريخ النقل' },
];

const studentOptions = props.students.map((s) => ({ value: s.id, label: s.user?.name ?? `طالب #${s.id}` }));
const mosqueOptions = props.mosques.map((m) => ({ value: m.id, label: m.name }));

const showModal = ref(false);
const processing = ref(false);
const errors = reactive<Record<string, string>>({});

const form = reactive({
    student_id: '' as string | number,
    to_mosque_id: '' as string | number,
    to_circle_id: '' as string | number,
    reason: '',
});

function clearErrors(): void {
    Object.keys(errors).forEach((key) => delete errors[key]);
}

async function submit(): Promise<void> {
    processing.value = true;
    clearErrors();

    try {
        await api.post(route('student-transfers.store'), { ...form });
        showModal.value = false;
        toast.success('تم نقل الطالب بنجاح.');
        router.reload({ only: ['transfers'] });
    } catch (error) {
        const validationErrors = api.extractValidationErrors(error);
        Object.entries(validationErrors).forEach(([key, messages]) => (errors[key] = messages[0]));
        if (Object.keys(validationErrors).length === 0) toast.error(api.extractErrorMessage(error));
    } finally {
        processing.value = false;
    }
}

function onPageChange(page: number): void {
    router.get(route('admin.student-transfers.page'), { page }, { preserveState: true });
}
</script>

<template>
    <Head title="نقل الطلاب" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h2 class="text-lg font-semibold leading-tight text-content-strong sm:text-xl">سجل نقل الطلاب</h2>
                <button
                    type="button"
                    class="focus-ring inline-flex min-h-11 items-center justify-center gap-2 rounded-lg btn-primary px-4 text-sm font-semibold transition duration-150 ease-standard"
                    @click="showModal = true"
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
                    {{ (row as unknown as StudentTransfer).student?.user?.name ?? '—' }}
                </template>
                <template #cell-from_mosque="{ row }">
                    {{ (row as unknown as StudentTransfer).from_mosque?.name ?? '—' }}
                </template>
                <template #cell-to_mosque="{ row }">
                    {{ (row as unknown as StudentTransfer).to_mosque?.name ?? '—' }}
                </template>
            </DataTable>
        </div>

        <Modal :show="showModal" @close="showModal = false">
            <form class="p-6" @submit.prevent="submit">
                <h3 class="mb-4 text-lg font-semibold text-content">نقل طالب إلى مسجد آخر</h3>
                <div class="space-y-4">
                    <FormSelect id="transfer-student" v-model="form.student_id" label="الطالب" :options="studentOptions" required :error="errors.student_id" />
                    <FormSelect id="transfer-mosque" v-model="form.to_mosque_id" label="المسجد الجديد" :options="mosqueOptions" required :error="errors.to_mosque_id" />
                    <FormInput id="transfer-reason" v-model="form.reason" label="سبب النقل" :error="errors.reason" />
                </div>
                <div class="mt-6 flex justify-end gap-3">
                    <button type="button" class="focus-ring inline-flex min-h-11 items-center justify-center gap-2 rounded-lg btn-secondary px-4 text-sm font-medium transition duration-150 ease-standard" @click="showModal = false">
                        إلغاء
                    </button>
                    <button type="submit" :disabled="processing" class="focus-ring inline-flex min-h-11 items-center justify-center gap-2 rounded-lg btn-primary px-4 text-sm font-semibold transition duration-150 ease-standard">
                        تأكيد النقل
                    </button>
                </div>
            </form>
        </Modal>
    </AuthenticatedLayout>
</template>
