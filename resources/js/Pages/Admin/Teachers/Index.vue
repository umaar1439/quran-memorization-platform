<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import DataTable, { type DataTableColumn } from '@/Components/DataTable.vue';
import Modal from '@/Components/Modal.vue';
import ConfirmDialog from '@/Components/ConfirmDialog.vue';
import FormInput from '@/Components/FormInput.vue';
import FormSelect from '@/Components/FormSelect.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import { useTeachers } from '@/Composables/useTeachers';
import type { TeacherIndexPageProps, TeacherPageModel } from '@/Types/api/teacher';

/**
 * صفحة عرض فقط: الحالة والنموذج والبحث والنداءات كلها في
 * useTeachers/TeacherApi. كل زر كتابة مرتبط بصلاحيته الدقيقة
 * (create/update/delete) بدل إظهاره ثم الانتهاء حتمًا بـ 403.
 */
const props = defineProps<TeacherIndexPageProps>();

const {
    form,
    errors,
    processing,
    canCreate,
    canUpdate,
    canDelete,
    circleChoices,
    statusOptions,
    showModal,
    editingTeacher,
    showDeleteConfirm,
    isCircleSelected,
    toggleCircle,
    openCreateModal,
    openEditModal,
    closeModal,
    submit,
    confirmDelete,
    cancelDelete,
    deleteTeacher,
    onSearch,
    onPageChange,
} = useTeachers(props);

const columns: DataTableColumn[] = [
    { key: 'name', label: 'الاسم' },
    { key: 'email', label: 'البريد الإلكتروني' },
    { key: 'specialization', label: 'التخصص' },
    { key: 'circles', label: 'الحلقات' },
    { key: 'status', label: 'الحالة' },
    { key: 'actions', label: 'إجراءات' },
];
</script>

<template>
    <Head title="إدارة المعلمين" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h2 class="text-lg font-semibold leading-tight text-content-strong sm:text-xl">إدارة المعلمين</h2>
                <button
                    v-if="canCreate"
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
                    {{ (row as TeacherPageModel).user?.name ?? '—' }}
                </template>
                <template #cell-email="{ row }">
                    {{ (row as TeacherPageModel).user?.email ?? '—' }}
                </template>
                <template #cell-specialization="{ row }">
                    {{ (row as TeacherPageModel).specialization ?? '—' }}
                </template>
                <template #cell-circles="{ row }">
                    {{ ((row as TeacherPageModel).user?.circlesTaught ?? []).map((circle) => circle.name).join('، ') || '—' }}
                </template>
                <template #cell-status="{ row }">
                    <StatusBadge :status="(row as TeacherPageModel).user?.status ?? 'active'" />
                </template>
                <template #cell-actions="{ row }">
                    <div v-if="canUpdate || canDelete" class="flex flex-wrap items-center gap-x-4 gap-y-2">
                        <button
                            v-if="canUpdate"
                            type="button"
                            class="link"
                            @click="openEditModal(row as TeacherPageModel)"
                        >
                            تعديل
                        </button>
                        <button
                            v-if="canDelete"
                            type="button"
                            class="link-danger"
                            @click="confirmDelete(row as TeacherPageModel)"
                        >
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
                    {{ editingTeacher ? 'تعديل معلم' : 'إضافة معلم جديد' }}
                </h3>
                <div class="space-y-4">
                    <FormInput id="teacher-name" v-model="form.name" label="الاسم" required :error="errors.name" />
                    <FormInput
                        id="teacher-email"
                        v-model="form.email"
                        type="email"
                        label="البريد الإلكتروني"
                        required
                        :error="errors.email"
                    />
                    <FormInput
                        id="teacher-password"
                        v-model="form.password"
                        type="password"
                        :label="editingTeacher ? 'كلمة المرور (اتركها فارغة لعدم التغيير)' : 'كلمة المرور'"
                        :required="!editingTeacher"
                        :error="errors.password"
                    />
                    <FormInput id="teacher-phone" v-model="form.phone" label="الهاتف" :error="errors.phone" />
                    <FormInput
                        id="teacher-specialization"
                        v-model="form.specialization"
                        label="التخصص"
                        :error="errors.specialization"
                    />
                    <FormInput
                        id="teacher-ijazah"
                        v-model="form.ijazah_details"
                        label="تفاصيل الإجازة"
                        :error="errors.ijazah_details"
                    />
                    <FormInput
                        id="teacher-hired"
                        v-model="form.hired_at"
                        type="date"
                        label="تاريخ التعيين"
                        :error="errors.hired_at"
                    />
                    <FormSelect
                        id="teacher-status"
                        v-model="form.status"
                        label="الحالة"
                        :options="statusOptions"
                        required
                        :error="errors.status"
                    />
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-content-body">
                            الحلقات المسندة
                            <span class="text-red-600 dark:text-red-400">*</span>
                        </label>
                        <p v-if="errors.circles" class="mt-1 text-sm text-red-600 dark:text-red-400">
                            {{ errors.circles }}
                        </p>
                        <div class="max-h-48 space-y-2 overflow-y-auto rounded-md border border-edge p-3">
                            <label
                                v-for="circle in circleChoices"
                                :key="circle.id"
                                class="flex items-center gap-2 text-sm"
                            >
                                <input
                                    type="checkbox"
                                    :checked="isCircleSelected(circle.id)"
                                    class="rounded border-edge-strong bg-surface text-teal-600 dark:text-teal-400 focus:ring-teal-500"
                                    @change="toggleCircle(circle.id)"
                                />
                                <span>{{ circle.label }}</span>
                            </label>
                            <p v-if="circleChoices.length === 0" class="text-sm text-content-muted">
                                لا توجد حلقات متاحة
                            </p>
                        </div>
                    </div>
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
            title="حذف المعلم"
            message="هل أنت متأكد من حذف هذا المعلم؟ سيتم إلغاء ربطه بجميع الحلقات."
            danger
            :processing="processing"
            @confirm="deleteTeacher"
            @cancel="cancelDelete"
        />
    </AuthenticatedLayout>
</template>
