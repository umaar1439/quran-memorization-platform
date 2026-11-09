<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import DataTable, { type DataTableColumn } from '@/Components/DataTable.vue';
import Modal from '@/Components/Modal.vue';
import ConfirmDialog from '@/Components/ConfirmDialog.vue';
import FormInput from '@/Components/FormInput.vue';
import FormSelect from '@/Components/FormSelect.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import { useMosques } from '@/Composables/useMosques';
import type { MosqueIndexPageProps, MosquePageModel } from '@/Types/api/mosque';

/**
 * صفحة عرض فقط: الحالة والنماذج والنداءات كلها في useMosques/MosqueApi.
 */
const props = defineProps<MosqueIndexPageProps>();

const {
    form,
    errors,
    processing,
    statusOptions,
    showModal,
    editingMosque,
    showDeleteConfirm,
    openCreateModal,
    openEditModal,
    closeModal,
    submit,
    confirmDelete,
    cancelDelete,
    deleteMosque,
    onSearch,
    onPageChange,
} = useMosques(props);

const columns: DataTableColumn[] = [
    { key: 'name', label: 'اسم المسجد' },
    { key: 'city', label: 'المدينة' },
    { key: 'circles_count', label: 'عدد الحلقات' },
    { key: 'status', label: 'الحالة' },
    { key: 'actions', label: 'إجراءات' },
];
</script>

<template>
    <Head title="المساجد" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h2 class="text-lg font-semibold leading-tight text-content-strong sm:text-xl">المساجد</h2>
                <button
                    type="button"
                    class="focus-ring inline-flex min-h-11 items-center justify-center gap-2 rounded-lg btn-primary px-4 text-sm font-semibold transition duration-150 ease-standard"
                    @click="openCreateModal"
                >
                    + إضافة مسجد
                </button>
            </div>
        </template>

        <div class="mx-auto max-w-7xl">
            <DataTable
                :columns="columns"
                :rows="mosques.data"
                :current-page="mosques.current_page"
                :last-page="mosques.last_page"
                :total="mosques.total"
                @search="onSearch"
                @update:page="onPageChange"
            >
                <template #cell-status="{ row }">
                    <StatusBadge :status="(row as unknown as MosquePageModel).status" />
                </template>
                <template #cell-actions="{ row }">
                    <div class="flex flex-wrap items-center gap-x-4 gap-y-2">
                        <button type="button" class="link" @click="openEditModal(row as unknown as MosquePageModel)">
                            تعديل
                        </button>
                        <button type="button" class="link-danger" @click="confirmDelete(row as unknown as MosquePageModel)">
                            حذف
                        </button>
                    </div>
                </template>
            </DataTable>
        </div>

        <Modal :show="showModal" @close="closeModal">
            <form class="p-6" @submit.prevent="submit">
                <h3 class="mb-4 text-lg font-semibold text-content">
                    {{ editingMosque ? 'تعديل مسجد' : 'إضافة مسجد جديد' }}
                </h3>
                <div class="space-y-4">
                    <FormInput id="mosque-name" v-model="form.name" label="اسم المسجد" required :error="errors.name" />
                    <FormInput id="mosque-city" v-model="form.city" label="المدينة" :error="errors.city" />
                    <FormInput id="mosque-address" v-model="form.address" label="العنوان" :error="errors.address" />
                    <FormInput id="mosque-phone" v-model="form.phone" label="الهاتف" :error="errors.phone" />
                    <FormSelect id="mosque-status" v-model="form.status" label="الحالة" :options="statusOptions" required :error="errors.status" />
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
            title="حذف المسجد"
            message="هل أنت متأكد من حذف هذا المسجد؟ سيتم حذف الحلقات المرتبطة به."
            danger
            :processing="processing"
            @confirm="deleteMosque"
            @cancel="cancelDelete"
        />
    </AuthenticatedLayout>
</template>
