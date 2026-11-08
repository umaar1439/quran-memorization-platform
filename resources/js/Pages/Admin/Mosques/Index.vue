<script setup lang="ts">
import { reactive, ref } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import DataTable, { type DataTableColumn } from '@/Components/DataTable.vue';
import Modal from '@/Components/Modal.vue';
import ConfirmDialog from '@/Components/ConfirmDialog.vue';
import FormInput from '@/Components/FormInput.vue';
import FormSelect from '@/Components/FormSelect.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import { useToast } from '@/Composables/useToast';
import type { Mosque, PaginatedResponse } from '@/Types';

const props = defineProps<{
    mosques: PaginatedResponse<Mosque>;
    filters: { search?: string };
}>();

const toast = useToast();

const columns: DataTableColumn[] = [
    { key: 'name', label: 'اسم المسجد' },
    { key: 'city', label: 'المدينة' },
    { key: 'circles_count', label: 'عدد الحلقات' },
    { key: 'status', label: 'الحالة' },
    { key: 'actions', label: 'إجراءات' },
];

const statusOptions = [
    { value: 'active', label: 'نشط' },
    { value: 'inactive', label: 'غير نشط' },
];

const showModal = ref(false);
const editingMosque = ref<Mosque | null>(null);
const showDeleteConfirm = ref(false);
const mosqueToDelete = ref<Mosque | null>(null);

const form = useForm({
    name: '',
    address: '',
    city: '',
    phone: '',
    status: 'active',
});

function openCreateModal(): void {
    editingMosque.value = null;
    form.reset();
    showModal.value = true;
}

function openEditModal(mosque: Mosque): void {
    editingMosque.value = mosque;
    form.name = mosque.name;
    form.address = mosque.address ?? '';
    form.city = mosque.city ?? '';
    form.phone = mosque.phone ?? '';
    form.status = mosque.status;
    showModal.value = true;
}

function submit(): void {
    if (editingMosque.value) {
        form.patch(route('admin.mosques.update', editingMosque.value.id), {
            onSuccess: () => {
                showModal.value = false;
                toast.success('تم تحديث بيانات المسجد بنجاح.');
            },
        });
    } else {
        form.post(route('admin.mosques.store'), {
            onSuccess: () => {
                showModal.value = false;
                toast.success('تم إضافة المسجد بنجاح.');
            },
        });
    }
}

function confirmDelete(mosque: Mosque): void {
    mosqueToDelete.value = mosque;
    showDeleteConfirm.value = true;
}

function deleteMosque(): void {
    if (!mosqueToDelete.value) return;

    router.delete(route('admin.mosques.destroy', mosqueToDelete.value.id), {
        onSuccess: () => {
            showDeleteConfirm.value = false;
            toast.success('تم حذف المسجد بنجاح.');
        },
    });
}

const searchState = reactive({ value: props.filters.search ?? '' });

function onSearch(value: string): void {
    searchState.value = value;
    router.get(route('admin.mosques.index'), { search: value }, { preserveState: true, replace: true });
}

function onPageChange(page: number): void {
    router.get(route('admin.mosques.index'), { ...props.filters, page }, { preserveState: true });
}
</script>

<template>
    <Head title="المساجد" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-content-strong">المساجد</h2>
                <button
                    type="button"
                    class="min-h-[44px] rounded-md bg-teal-600 px-4 py-2 text-sm font-medium text-white hover:bg-teal-700"
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
                    <StatusBadge :status="(row as unknown as Mosque).status" />
                </template>
                <template #cell-actions="{ row }">
                    <div class="flex gap-2">
                        <button type="button" class="text-sm font-medium text-teal-600 dark:text-teal-400 hover:underline" @click="openEditModal(row as unknown as Mosque)">
                            تعديل
                        </button>
                        <button type="button" class="text-sm font-medium text-red-600 dark:text-red-400 hover:underline" @click="confirmDelete(row as unknown as Mosque)">
                            حذف
                        </button>
                    </div>
                </template>
            </DataTable>
        </div>

        <Modal :show="showModal" @close="showModal = false">
            <form class="p-6" @submit.prevent="submit">
                <h3 class="mb-4 text-lg font-semibold text-content">
                    {{ editingMosque ? 'تعديل مسجد' : 'إضافة مسجد جديد' }}
                </h3>
                <div class="space-y-4">
                    <FormInput id="mosque-name" v-model="form.name" label="اسم المسجد" required :error="form.errors.name" />
                    <FormInput id="mosque-city" v-model="form.city" label="المدينة" :error="form.errors.city" />
                    <FormInput id="mosque-address" v-model="form.address" label="العنوان" :error="form.errors.address" />
                    <FormInput id="mosque-phone" v-model="form.phone" label="الهاتف" :error="form.errors.phone" />
                    <FormSelect id="mosque-status" v-model="form.status" label="الحالة" :options="statusOptions" required :error="form.errors.status" />
                </div>
                <div class="mt-6 flex justify-end gap-3">
                    <button type="button" class="min-h-[44px] rounded-md border border-edge-strong px-4 py-2 text-sm" @click="showModal = false">
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
            title="حذف المسجد"
            message="هل أنت متأكد من حذف هذا المسجد؟ سيتم حذف الحلقات المرتبطة به."
            danger
            @confirm="deleteMosque"
            @cancel="showDeleteConfirm = false"
        />
    </AuthenticatedLayout>
</template>
