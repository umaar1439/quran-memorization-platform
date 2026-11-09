<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import DataTable, { type DataTableColumn } from '@/Components/DataTable.vue';
import Modal from '@/Components/Modal.vue';
import ConfirmDialog from '@/Components/ConfirmDialog.vue';
import FormInput from '@/Components/FormInput.vue';
import FormSelect from '@/Components/FormSelect.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import { useStudents } from '@/Composables/useStudents';
import type { StudentIndexPageProps, StudentPageModel } from '@/Types/api/student';

/**
 * صفحة عرض فقط: الحالة والنماذج والفلاتر والنداءات كلها في
 * useStudents/StudentApi. أزرار الكتابة مرتبطة بـ canManage.
 */
const props = defineProps<StudentIndexPageProps>();

const {
    form,
    errors,
    processing,
    filters,
    canManage,
    circleOptions,
    statusOptions,
    filterFields,
    showModal,
    editingStudent,
    showDeleteConfirm,
    openCreateModal,
    openEditModal,
    closeModal,
    submit,
    confirmDelete,
    cancelDelete,
    deleteStudent,
    onSearch,
    onPageChange,
    onFilterChange,
} = useStudents(props);

const columns: DataTableColumn[] = [
    { key: 'name', label: 'الاسم' },
    { key: 'enrollment_number', label: 'رقم القيد' },
    { key: 'circle', label: 'الحلقة' },
    { key: 'status', label: 'الحالة' },
    { key: 'actions', label: 'إجراءات' },
];
</script>

<template>
    <Head title="إدارة الطلاب" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h2 class="text-lg font-semibold leading-tight text-content-strong sm:text-xl">إدارة الطلاب</h2>
                <button
                    v-if="canManage"
                    type="button"
                    class="focus-ring inline-flex min-h-11 items-center justify-center gap-2 rounded-lg btn-primary px-4 text-sm font-semibold transition duration-150 ease-standard"
                    @click="openCreateModal"
                >
                    + إضافة طالب
                </button>
            </div>
        </template>

        <div class="mx-auto max-w-7xl space-y-4">
            <div class="card grid gap-3 p-3 sm:grid-cols-3 sm:p-4">
                <label v-for="field in filterFields" :key="field.key" class="block min-w-0">
                    <span class="mb-1.5 block text-sm font-medium text-content-body">{{ field.label }}</span>
                    <select
                        class="field min-h-11"
                        :value="filters[field.key] ?? ''"
                        @change="onFilterChange(field.key, $event)"
                    >
                        <option value="">{{ field.allLabel }}</option>
                        <option v-for="option in field.options" :key="option.value" :value="option.value">
                            {{ option.label }}
                        </option>
                    </select>
                </label>
            </div>

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
                    {{ (row as StudentPageModel).user?.name ?? '—' }}
                </template>
                <template #cell-enrollment_number="{ row }">
                    {{ (row as StudentPageModel).enrollment_number ?? '—' }}
                </template>
                <template #cell-circle="{ row }">
                    {{ (row as StudentPageModel).circle?.name ?? '—' }}
                </template>
                <template #cell-status="{ row }">
                    <StatusBadge :status="(row as StudentPageModel).status" />
                </template>
                <template #cell-actions="{ row }">
                    <div v-if="canManage" class="flex flex-wrap items-center gap-x-4 gap-y-2">
                        <button type="button" class="link" @click="openEditModal(row as StudentPageModel)">
                            تعديل
                        </button>
                        <button type="button" class="link-danger" @click="confirmDelete(row as StudentPageModel)">
                            حذف
                        </button>
                    </div>
                    <span v-else class="text-sm text-content-muted">—</span>
                </template>
            </DataTable>
        </div>

        <Modal :show="showModal" @close="closeModal">
            <form class="p-6" @submit.prevent="submit">
                <h3 class="mb-4 text-lg font-semibold text-content">
                    {{ editingStudent ? 'تعديل طالب' : 'إضافة طالب جديد' }}
                </h3>
                <div class="space-y-4">
                    <FormInput id="student-name" v-model="form.name" label="الاسم" required :error="errors.name" />
                    <FormInput
                        id="student-email"
                        v-model="form.email"
                        type="email"
                        label="البريد الإلكتروني"
                        required
                        :error="errors.email"
                    />
                    <FormInput
                        id="student-password"
                        v-model="form.password"
                        type="password"
                        :label="editingStudent ? 'كلمة المرور (اتركها فارغة لعدم التغيير)' : 'كلمة المرور'"
                        :required="!editingStudent"
                        :error="errors.password"
                    />
                    <FormInput
                        id="student-enrollment"
                        v-model="form.enrollment_number"
                        label="رقم القيد"
                        required
                        :error="errors.enrollment_number"
                    />
                    <FormInput
                        id="student-birth"
                        v-model="form.birth_date"
                        type="date"
                        label="تاريخ الميلاد"
                        :error="errors.birth_date"
                    />
                    <FormInput
                        id="student-juz"
                        v-model="form.current_juz"
                        type="number"
                        label="الجزء الحالي"
                        :error="errors.current_juz"
                    />
                    <FormSelect
                        id="student-circle"
                        v-model="form.circle_id"
                        label="الحلقة"
                        :options="circleOptions"
                        required
                        :error="errors.circle_id"
                    />
                    <FormSelect
                        id="student-status"
                        v-model="form.status"
                        label="الحالة"
                        :options="statusOptions"
                        required
                        :error="errors.status"
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
                        حفظ
                    </button>
                </div>
            </form>
        </Modal>

        <ConfirmDialog
            :show="showDeleteConfirm"
            title="حذف الطالب"
            message="هل أنت متأكد من حذف هذا الطالب؟ سيُحذف حسابه أيضاً، وتبقى سجلاته التاريخية."
            danger
            :processing="processing"
            @confirm="deleteStudent"
            @cancel="cancelDelete"
        />
    </AuthenticatedLayout>
</template>
