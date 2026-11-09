<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import DataTable, { type DataTableColumn } from '@/Components/DataTable.vue';
import Modal from '@/Components/Modal.vue';
import ConfirmDialog from '@/Components/ConfirmDialog.vue';
import FormInput from '@/Components/FormInput.vue';
import FormSelect from '@/Components/FormSelect.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import { useCircles } from '@/Composables/useCircles';
import type { CircleIndexPageProps, CirclePageModel } from '@/Types/api/circle';

/**
 * صفحة عرض فقط: الحالة والنماذج والنداءات كلها في useCircles/CircleApi.
 * أزرار الكتابة مرتبطة بـ canManage المستخرجة من صلاحيات المستخدم.
 */
const props = defineProps<CircleIndexPageProps>();

const {
    form,
    errors,
    processing,
    statusOptions,
    canManage,
    mosqueOptions,
    teacherOptions,
    showModal,
    editingCircle,
    showDeleteConfirm,
    openCreateModal,
    openEditModal,
    closeModal,
    submit,
    confirmDelete,
    cancelDelete,
    deleteCircle,
    onSearch,
    onPageChange,
} = useCircles(props);

const columns: DataTableColumn[] = [
    { key: 'name', label: 'اسم الحلقة' },
    { key: 'mosque', label: 'المسجد' },
    { key: 'teacher', label: 'المعلم' },
    { key: 'student_profiles_count', label: 'عدد الطلاب' },
    { key: 'status', label: 'الحالة' },
    { key: 'actions', label: 'إجراءات' },
];
</script>

<template>
    <Head title="الحلقات" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h2 class="text-lg font-semibold leading-tight text-content-strong sm:text-xl">الحلقات</h2>
                <button
                    v-if="canManage"
                    type="button"
                    class="focus-ring inline-flex min-h-11 items-center justify-center gap-2 rounded-lg btn-primary px-4 text-sm font-semibold transition duration-150 ease-standard"
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
                    {{ (row as unknown as CirclePageModel).mosque?.name ?? '—' }}
                </template>
                <template #cell-teacher="{ row }">
                    {{ (row as unknown as CirclePageModel).teacher?.name ?? 'غير معيّن' }}
                </template>
                <template #cell-status="{ row }">
                    <StatusBadge :status="(row as unknown as CirclePageModel).status" />
                </template>
                <template #cell-actions="{ row }">
                    <div class="flex flex-wrap items-center gap-x-4 gap-y-2">
                        <Link :href="route('admin.circles.show', (row as unknown as CirclePageModel).id)" class="link">
                            عرض
                        </Link>
                        <template v-if="canManage">
                            <button type="button" class="link" @click="openEditModal(row as unknown as CirclePageModel)">
                                تعديل
                            </button>
                            <button type="button" class="link-danger" @click="confirmDelete(row as unknown as CirclePageModel)">
                                حذف
                            </button>
                        </template>
                    </div>
                </template>
            </DataTable>
        </div>

        <Modal :show="showModal" @close="closeModal">
            <form class="p-6" @submit.prevent="submit">
                <h3 class="mb-4 text-lg font-semibold text-content">
                    {{ editingCircle ? 'تعديل حلقة' : 'إضافة حلقة جديدة' }}
                </h3>
                <div class="space-y-4">
                    <FormSelect id="circle-mosque" v-model="form.mosque_id" label="المسجد" :options="mosqueOptions" required :error="errors.mosque_id" />
                    <FormInput id="circle-name" v-model="form.name" label="اسم الحلقة" required :error="errors.name" />
                    <FormSelect id="circle-teacher" v-model="form.teacher_id" label="المعلم" :options="teacherOptions" :error="errors.teacher_id" />
                    <FormInput id="circle-level" v-model="form.level" label="المستوى" :error="errors.level" />
                    <FormInput id="circle-capacity" v-model="form.capacity" type="number" label="السعة القصوى" :error="errors.capacity" />
                    <FormSelect id="circle-status" v-model="form.status" label="الحالة" :options="statusOptions" required :error="errors.status" />
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

        <ConfirmDialog
            :show="showDeleteConfirm"
            title="حذف الحلقة"
            message="هل أنت متأكد من حذف هذه الحلقة؟"
            danger
            :processing="processing"
            @confirm="deleteCircle"
            @cancel="cancelDelete"
        />
    </AuthenticatedLayout>
</template>
