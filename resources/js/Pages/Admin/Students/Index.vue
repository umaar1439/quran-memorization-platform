<script setup lang="ts">
import { ref } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import DataTable, { type DataTableColumn } from '@/Components/DataTable.vue';
import Modal from '@/Components/Modal.vue';
import ConfirmDialog from '@/Components/ConfirmDialog.vue';
import FormInput from '@/Components/FormInput.vue';
import FormSelect from '@/Components/FormSelect.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import { useToast } from '@/Composables/useToast';
import type { PaginatedResponse, StudentProfile } from '@/Types';

type StudentRow = StudentProfile;

interface CircleOption {
    id: number;
    name: string;
    mosque_id?: number | null;
    mosque?: { name: string } | null;
}

const props = defineProps<{
    students: PaginatedResponse<StudentRow>;
    circles: CircleOption[];
    filters: { search?: string; circle_id?: string };
}>();

const toast = useToast();

const columns: DataTableColumn[] = [
    { key: 'name', label: 'الاسم' },
    { key: 'enrollment_number', label: 'رقم القيد' },
    { key: 'circle', label: 'الحلقة' },
    { key: 'status', label: 'الحالة' },
    { key: 'actions', label: 'إجراءات' },
];

const statusOptions = [
    { value: 'active', label: 'نشط' },
    { value: 'transferred', label: 'منقول' },
    { value: 'graduated', label: 'متخرج' },
    { value: 'withdrawn', label: 'منسحب' },
];

const circleOptions = props.circles.map((circle) => ({
    value: circle.id,
    label: circle.mosque ? `${circle.name} (${circle.mosque.name})` : circle.name,
}));

const showModal = ref(false);
const editingStudent = ref<StudentRow | null>(null);
const showDeleteConfirm = ref(false);
const studentToDelete = ref<StudentRow | null>(null);

const form = useForm({
    name: '',
    email: '',
    password: '',
    enrollment_number: '',
    birth_date: '',
    current_juz: '' as string | number,
    circle_id: '' as string | number,
    status: 'active',
});

function openCreateModal(): void {
    editingStudent.value = null;
    form.reset();
    showModal.value = true;
}

function openEditModal(student: StudentRow): void {
    editingStudent.value = student;
    form.name = student.user?.name ?? '';
    form.email = student.user?.email ?? '';
    form.password = '';
    form.enrollment_number = student.enrollment_number ?? '';
    form.birth_date = student.birth_date ?? '';
    form.current_juz = student.current_juz ?? '';
    form.circle_id = student.circle_id ?? '';
    form.status = student.status ?? 'active';
    showModal.value = true;
}

function submit(): void {
    if (editingStudent.value) {
        form.patch(route('admin.students.update', editingStudent.value.id), {
            onSuccess: () => {
                showModal.value = false;
                toast.success('تم تحديث بيانات الطالب بنجاح.');
            },
        });
    } else {
        form.post(route('admin.students.store'), {
            onSuccess: () => {
                showModal.value = false;
                toast.success('تم إضافة الطالب بنجاح.');
            },
        });
    }
}

function confirmDelete(student: StudentRow): void {
    studentToDelete.value = student;
    showDeleteConfirm.value = true;
}

function deleteStudent(): void {
    if (!studentToDelete.value) return;
    router.delete(route('admin.students.destroy', studentToDelete.value.id), {
        onSuccess: () => {
            showDeleteConfirm.value = false;
            toast.success('تم حذف الطالب بنجاح.');
        },
    });
}

function onSearch(value: string): void {
    router.get(route('admin.students.index'), { ...props.filters, search: value }, { preserveState: true, replace: true });
}

function onPageChange(page: number): void {
    router.get(route('admin.students.index'), { ...props.filters, page }, { preserveState: true });
}
</script>

<template>
    <Head title="إدارة الطلاب" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h2 class="text-lg font-semibold leading-tight text-content-strong sm:text-xl">إدارة الطلاب</h2>
                <button
                    type="button"
                    class="focus-ring inline-flex min-h-11 items-center justify-center gap-2 rounded-lg btn-primary px-4 text-sm font-semibold transition duration-150 ease-standard"
                    @click="openCreateModal"
                >
                    إضافة طالب
                </button>
            </div>
        </template>

        <div class="mx-auto max-w-7xl">
            <DataTable
                :columns="columns"
                :rows="students.data"
                :current-page="students.current_page"
                :last-page="students.last_page"
                :total="students.total"
                @search="onSearch"
                @update:page="onPageChange"
            >
                <template #cell-name="{ row }">
                    {{ (row as StudentRow).user?.name ?? '—' }}
                </template>
                <template #cell-enrollment_number="{ row }">
                    {{ (row as StudentRow).enrollment_number ?? '—' }}
                </template>
                <template #cell-circle="{ row }">
                    {{ (row as StudentRow).circle?.name ?? '—' }}
                </template>
                <template #cell-status="{ row }">
                    <StatusBadge :status="(row as StudentRow).status ?? 'active'" />
                </template>
                <template #cell-actions="{ row }">
                    <div class="flex flex-wrap items-center gap-x-4 gap-y-2">
                        <button type="button" class="link" @click="openEditModal(row as StudentRow)">
                            تعديل
                        </button>
                        <button type="button" class="link-danger" @click="confirmDelete(row as StudentRow)">
                            حذف
                        </button>
                    </div>
                </template>
            </DataTable>
        </div>

        <Modal :show="showModal" @close="showModal = false">
            <form class="p-6" @submit.prevent="submit">
                <h3 class="mb-4 text-lg font-semibold text-content">
                    {{ editingStudent ? 'تعديل طالب' : 'إضافة طالب جديد' }}
                </h3>
                <div class="space-y-4">
                    <FormInput id="student-name" v-model="form.name" label="الاسم" required :error="form.errors.name" />
                    <FormInput id="student-email" v-model="form.email" type="email" label="البريد الإلكتروني" required :error="form.errors.email" />
                    <FormInput
                        id="student-password"
                        v-model="form.password"
                        type="password"
                        :label="editingStudent ? 'كلمة المرور (اتركها فارغة لعدم التغيير)' : 'كلمة المرور'"
                        :required="!editingStudent"
                        :error="form.errors.password"
                    />
                    <FormInput id="student-enrollment" v-model="form.enrollment_number" label="رقم القيد" required :error="form.errors.enrollment_number" />
                    <FormInput id="student-birth" v-model="form.birth_date" type="date" label="تاريخ الميلاد" :error="form.errors.birth_date" />
                    <FormInput id="student-juz" v-model="form.current_juz" type="number" label="الجزء الحالي" :error="form.errors.current_juz" />
                    <FormSelect id="student-circle" v-model="form.circle_id" label="الحلقة" :options="circleOptions" required :error="form.errors.circle_id" />
                    <FormSelect id="student-status" v-model="form.status" label="الحالة" :options="statusOptions" required :error="form.errors.status" />
                </div>
                <div class="mt-6 flex justify-end gap-3">
                    <button type="button" class="focus-ring inline-flex min-h-11 items-center justify-center gap-2 rounded-lg btn-secondary px-4 text-sm font-medium transition duration-150 ease-standard" @click="showModal = false">
                        إلغاء
                    </button>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="focus-ring inline-flex min-h-11 items-center justify-center gap-2 rounded-lg btn-primary px-4 text-sm font-semibold transition duration-150 ease-standard"
                    >
                        حفظ
                    </button>
                </div>
            </form>
        </Modal>

        <ConfirmDialog
            :show="showDeleteConfirm"
            title="حذف الطالب"
            message="هل أنت متأكد من حذف هذا الطالب؟"
            danger
            @confirm="deleteStudent"
            @cancel="showDeleteConfirm = false"
        />
    </AuthenticatedLayout>
</template>
