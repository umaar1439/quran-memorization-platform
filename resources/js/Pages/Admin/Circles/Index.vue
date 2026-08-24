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
import type { Circle, Mosque, PaginatedResponse, User } from '@/Types';

const props = defineProps<{
    circles: PaginatedResponse<Circle>;
    mosques: Pick<Mosque, 'id' | 'name'>[];
    teachers: Pick<User, 'id' | 'name'>[];
    filters: { search?: string; mosque_id?: string };
}>();

const toast = useToast();

const columns: DataTableColumn[] = [
    { key: 'name', label: 'اسم الحلقة' },
    { key: 'mosque', label: 'المسجد' },
    { key: 'teacher', label: 'المعلم' },
    { key: 'student_profiles_count', label: 'عدد الطلاب' },
    { key: 'status', label: 'الحالة' },
    { key: 'actions', label: 'إجراءات' },
];

const statusOptions = [
    { value: 'active', label: 'نشط' },
    { value: 'inactive', label: 'غير نشط' },
];

const mosqueOptions = props.mosques.map((m) => ({ value: m.id, label: m.name }));
const teacherOptions = props.teachers.map((t) => ({ value: t.id, label: t.name }));

const showModal = ref(false);
const editingCircle = ref<Circle | null>(null);
const showDeleteConfirm = ref(false);
const circleToDelete = ref<Circle | null>(null);

const form = useForm({
    mosque_id: '' as string | number,
    teacher_id: '' as string | number,
    name: '',
    level: '',
    capacity: '' as string | number,
    status: 'active',
});

function openCreateModal(): void {
    editingCircle.value = null;
    form.reset();
    showModal.value = true;
}

function openEditModal(circle: Circle): void {
    editingCircle.value = circle;
    form.mosque_id = circle.mosque_id;
    form.teacher_id = circle.teacher_id ?? '';
    form.name = circle.name;
    form.level = circle.level ?? '';
    form.capacity = circle.capacity ?? '';
    form.status = circle.status;
    showModal.value = true;
}

function submit(): void {
    if (editingCircle.value) {
        form.patch(route('admin.circles.update', editingCircle.value.id), {
            onSuccess: () => {
                showModal.value = false;
                toast.success('تم تحديث بيانات الحلقة بنجاح.');
            },
        });
    } else {
        form.post(route('admin.circles.store'), {
            onSuccess: () => {
                showModal.value = false;
                toast.success('تم إضافة الحلقة بنجاح.');
            },
        });
    }
}

function confirmDelete(circle: Circle): void {
    circleToDelete.value = circle;
    showDeleteConfirm.value = true;
}

function deleteCircle(): void {
    if (!circleToDelete.value) return;

    router.delete(route('admin.circles.destroy', circleToDelete.value.id), {
        onSuccess: () => {
            showDeleteConfirm.value = false;
            toast.success('تم حذف الحلقة بنجاح.');
        },
    });
}

function onSearch(value: string): void {
    router.get(route('admin.circles.index'), { ...props.filters, search: value }, { preserveState: true, replace: true });
}

function onPageChange(page: number): void {
    router.get(route('admin.circles.index'), { ...props.filters, page }, { preserveState: true });
}
</script>

<template>
    <Head title="الحلقات" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">الحلقات</h2>
                <button
                    type="button"
                    class="min-h-[44px] rounded-md bg-teal-600 px-4 py-2 text-sm font-medium text-white hover:bg-teal-700"
                    @click="openCreateModal"
                >
                    + إضافة حلقة
                </button>
            </div>
        </template>

        <div class="mx-auto max-w-7xl">
            <DataTable
                :columns="columns"
                :rows="circles.data"
                :current-page="circles.current_page"
                :last-page="circles.last_page"
                :total="circles.total"
                @search="onSearch"
                @update:page="onPageChange"
            >
                <template #cell-mosque="{ row }">
                    {{ (row as unknown as Circle).mosque?.name ?? '—' }}
                </template>
                <template #cell-teacher="{ row }">
                    {{ (row as unknown as Circle).teacher?.name ?? 'غير معيّن' }}
                </template>
                <template #cell-status="{ row }">
                    <StatusBadge :status="(row as unknown as Circle).status" />
                </template>
                <template #cell-actions="{ row }">
                    <div class="flex gap-2">
                        <button type="button" class="text-sm font-medium text-teal-600 hover:underline" @click="openEditModal(row as unknown as Circle)">
                            تعديل
                        </button>
                        <button type="button" class="text-sm font-medium text-red-600 hover:underline" @click="confirmDelete(row as unknown as Circle)">
                            حذف
                        </button>
                    </div>
                </template>
            </DataTable>
        </div>

        <Modal :show="showModal" @close="showModal = false">
            <form class="p-6" @submit.prevent="submit">
                <h3 class="mb-4 text-lg font-semibold text-gray-900">
                    {{ editingCircle ? 'تعديل حلقة' : 'إضافة حلقة جديدة' }}
                </h3>
                <div class="space-y-4">
                    <FormSelect id="circle-mosque" v-model="form.mosque_id" label="المسجد" :options="mosqueOptions" required :error="form.errors.mosque_id" />
                    <FormInput id="circle-name" v-model="form.name" label="اسم الحلقة" required :error="form.errors.name" />
                    <FormSelect id="circle-teacher" v-model="form.teacher_id" label="المعلم" :options="teacherOptions" :error="form.errors.teacher_id" />
                    <FormInput id="circle-level" v-model="form.level" label="المستوى" :error="form.errors.level" />
                    <FormInput id="circle-capacity" v-model="form.capacity" type="number" label="السعة القصوى" :error="form.errors.capacity" />
                    <FormSelect id="circle-status" v-model="form.status" label="الحالة" :options="statusOptions" required :error="form.errors.status" />
                </div>
                <div class="mt-6 flex justify-end gap-3">
                    <button type="button" class="min-h-[44px] rounded-md border border-gray-300 px-4 py-2 text-sm" @click="showModal = false">
                        إلغاء
                    </button>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="min-h-[44px] rounded-md bg-teal-600 px-4 py-2 text-sm font-medium text-white disabled:opacity-50"
                    >
                        حفظ
                    </button>
                </div>
            </form>
        </Modal>

        <ConfirmDialog
            :show="showDeleteConfirm"
            title="حذف الحلقة"
            message="هل أنت متأكد من حذف هذه الحلقة؟"
            danger
            @confirm="deleteCircle"
            @cancel="showDeleteConfirm = false"
        />
    </AuthenticatedLayout>
</template>
