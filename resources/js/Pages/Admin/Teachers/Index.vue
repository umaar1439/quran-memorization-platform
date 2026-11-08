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
import type { Circle, PaginatedResponse, TeacherProfile, User } from '@/Types';

interface TeacherRow extends TeacherProfile {
    user?: User & { circlesTaught?: Circle[] };
}

interface CircleOption {
    id: number;
    name: string;
    mosque_id?: number | null;
    mosque?: { name: string } | null;
}

const props = defineProps<{
    teachers: PaginatedResponse<TeacherRow>;
    circles: CircleOption[];
    filters: { search?: string };
}>();

const toast = useToast();

const columns: DataTableColumn[] = [
    { key: 'name', label: 'الاسم' },
    { key: 'email', label: 'البريد الإلكتروني' },
    { key: 'specialization', label: 'التخصص' },
    { key: 'circles', label: 'الحلقات' },
    { key: 'status', label: 'الحالة' },
    { key: 'actions', label: 'إجراءات' },
];

const statusOptions = [
    { value: 'active', label: 'نشط' },
    { value: 'inactive', label: 'غير نشط' },
    { value: 'suspended', label: 'موقوف' },
];

const showModal = ref(false);
const editingTeacher = ref<TeacherRow | null>(null);
const showDeleteConfirm = ref(false);
const teacherToDelete = ref<TeacherRow | null>(null);

const form = useForm({
    name: '',
    email: '',
    password: '',
    phone: '',
    specialization: '',
    ijazah_details: '',
    hired_at: '',
    status: 'active',
    circles: [] as number[],
});

function toggleCircle(id: number): void {
    const index = form.circles.indexOf(id);
    if (index === -1) {
        form.circles.push(id);
    } else {
        form.circles.splice(index, 1);
    }
}

function circleLabel(circle: CircleOption): string {
    return circle.mosque ? `${circle.name} (${circle.mosque.name})` : circle.name;
}

function openCreateModal(): void {
    editingTeacher.value = null;
    form.reset();
    showModal.value = true;
}

function openEditModal(teacher: TeacherRow): void {
    editingTeacher.value = teacher;
    form.name = teacher.user?.name ?? '';
    form.email = teacher.user?.email ?? '';
    form.password = '';
    form.phone = teacher.user?.phone ?? '';
    form.specialization = teacher.specialization ?? '';
    form.ijazah_details = teacher.ijazah_details ?? '';
    form.hired_at = teacher.hired_at ?? '';
    form.status = teacher.user?.status ?? 'active';
    form.circles = (teacher.user?.circlesTaught ?? []).map((c) => c.id);
    showModal.value = true;
}

function submit(): void {
    if (editingTeacher.value) {
        form.patch(route('admin.teachers.update', editingTeacher.value.id), {
            onSuccess: () => {
                showModal.value = false;
                toast.success('تم تحديث بيانات المعلم بنجاح.');
            },
        });
    } else {
        form.post(route('admin.teachers.store'), {
            onSuccess: () => {
                showModal.value = false;
                toast.success('تم إضافة المعلم بنجاح.');
            },
        });
    }
}

function confirmDelete(teacher: TeacherRow): void {
    teacherToDelete.value = teacher;
    showDeleteConfirm.value = true;
}

function deleteTeacher(): void {
    if (!teacherToDelete.value) return;
    router.delete(route('admin.teachers.destroy', teacherToDelete.value.id), {
        onSuccess: () => {
            showDeleteConfirm.value = false;
            toast.success('تم حذف المعلم بنجاح.');
        },
    });
}

function onSearch(value: string): void {
    router.get(route('admin.teachers.index'), { ...props.filters, search: value }, { preserveState: true, replace: true });
}

function onPageChange(page: number): void {
    router.get(route('admin.teachers.index'), { ...props.filters, page }, { preserveState: true });
}
</script>

<template>
    <Head title="إدارة المعلمين" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h2 class="text-lg font-semibold leading-tight text-content-strong sm:text-xl">إدارة المعلمين</h2>
                <button
                    type="button"
                    class="focus-ring inline-flex min-h-11 items-center justify-center gap-2 rounded-lg btn-primary px-4 text-sm font-semibold transition duration-150 ease-standard"
                    @click="openCreateModal"
                >
                    إضافة معلم
                </button>
            </div>
        </template>

        <div class="mx-auto max-w-7xl">
            <DataTable
                :columns="columns"
                :rows="teachers.data"
                :current-page="teachers.current_page"
                :last-page="teachers.last_page"
                :total="teachers.total"
                @search="onSearch"
                @update:page="onPageChange"
            >
                <template #cell-name="{ row }">
                    {{ (row as TeacherRow).user?.name ?? '—' }}
                </template>
                <template #cell-email="{ row }">
                    {{ (row as TeacherRow).user?.email ?? '—' }}
                </template>
                <template #cell-specialization="{ row }">
                    {{ (row as TeacherRow).specialization ?? '—' }}
                </template>
                <template #cell-circles="{ row }">
                    {{ ((row as TeacherRow).user?.circlesTaught ?? []).map((c) => c.name).join('، ') || '—' }}
                </template>
                <template #cell-status="{ row }">
                    <StatusBadge :status="(row as TeacherRow).user?.status ?? 'active'" />
                </template>
                <template #cell-actions="{ row }">
                    <div class="flex flex-wrap items-center gap-x-4 gap-y-2">
                        <button type="button" class="link" @click="openEditModal(row as TeacherRow)">
                            تعديل
                        </button>
                        <button type="button" class="link-danger" @click="confirmDelete(row as TeacherRow)">
                            حذف
                        </button>
                    </div>
                </template>
            </DataTable>
        </div>

        <Modal :show="showModal" @close="showModal = false">
            <form class="p-6" @submit.prevent="submit">
                <h3 class="mb-4 text-lg font-semibold text-content">
                    {{ editingTeacher ? 'تعديل معلم' : 'إضافة معلم جديد' }}
                </h3>
                <div class="space-y-4">
                    <FormInput id="teacher-name" v-model="form.name" label="الاسم" required :error="form.errors.name" />
                    <FormInput id="teacher-email" v-model="form.email" type="email" label="البريد الإلكتروني" required :error="form.errors.email" />
                    <FormInput
                        id="teacher-password"
                        v-model="form.password"
                        type="password"
                        :label="editingTeacher ? 'كلمة المرور (اتركها فارغة لعدم التغيير)' : 'كلمة المرور'"
                        :required="!editingTeacher"
                        :error="form.errors.password"
                    />
                    <FormInput id="teacher-phone" v-model="form.phone" label="الهاتف" :error="form.errors.phone" />
                    <FormInput id="teacher-specialization" v-model="form.specialization" label="التخصص" :error="form.errors.specialization" />
                    <FormInput id="teacher-ijazah" v-model="form.ijazah_details" label="تفاصيل الإجازة" :error="form.errors.ijazah_details" />
                    <FormInput id="teacher-hired" v-model="form.hired_at" type="date" label="تاريخ التعيين" :error="form.errors.hired_at" />
                    <FormSelect id="teacher-status" v-model="form.status" label="الحالة" :options="statusOptions" required :error="form.errors.status" />

                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-content-body">
                            الحلقات المسندة
                            <span class="text-red-600 dark:text-red-400">*</span>
                        </label>
                        <p v-if="form.errors.circles" class="mt-1 text-sm text-red-600 dark:text-red-400">{{ form.errors.circles }}</p>
                        <div class="max-h-48 space-y-2 overflow-y-auto rounded-md border border-edge p-3">
                            <label v-for="circle in circles" :key="circle.id" class="flex items-center gap-2 text-sm">
                                <input
                                    type="checkbox"
                                    :checked="form.circles.includes(circle.id)"
                                    class="rounded border-edge-strong bg-surface text-teal-600 dark:text-teal-400 focus:ring-teal-500"
                                    @change="toggleCircle(circle.id)"
                                />
                                <span>{{ circleLabel(circle) }}</span>
                            </label>
                            <p v-if="circles.length === 0" class="text-sm text-content-muted">لا توجد حلقات متاحة</p>
                        </div>
                    </div>
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
            title="حذف المعلم"
            message="هل أنت متأكد من حذف هذا المعلم؟ سيتم إلغاء ربطه بجميع الحلقات."
            danger
            @confirm="deleteTeacher"
            @cancel="showDeleteConfirm = false"
        />
    </AuthenticatedLayout>
</template>
