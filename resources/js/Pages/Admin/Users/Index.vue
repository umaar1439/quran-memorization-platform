<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import DataTable, { type DataTableColumn } from '@/Components/DataTable.vue';
import Modal from '@/Components/Modal.vue';
import ConfirmDialog from '@/Components/ConfirmDialog.vue';
import FormInput from '@/Components/FormInput.vue';
import FormSelect from '@/Components/FormSelect.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import { useUserManagement } from '@/Composables/useUserManagement';
import type { UserIndexPageProps, UserPageModel } from '@/Types/api/user';

/**
 * العرض فقط: كل الحالة والمنطق في useUserManagement، وكل نداء شبكة في
 * UserApi. لا useForm ولا router للكتابة هنا.
 */
const props = defineProps<UserIndexPageProps>();

const {
    form,
    errors,
    processing,
    roleOptions,
    statusOptions,
    showModal,
    editingUser,
    showDeleteConfirm,
    roleLabel,
    openCreateModal,
    openEditModal,
    closeModal,
    submit,
    confirmDelete,
    cancelDelete,
    deleteUser,
    onSearch,
    onPageChange,
} = useUserManagement(props);

const columns: DataTableColumn[] = [
    { key: 'name', label: 'الاسم' },
    { key: 'email', label: 'البريد الإلكتروني' },
    { key: 'roles', label: 'الدور' },
    { key: 'status', label: 'الحالة' },
    { key: 'actions', label: 'إجراءات' },
];
</script>

<template>
    <Head title="المستخدمون" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h2 class="text-lg font-semibold leading-tight text-content-strong sm:text-xl">المستخدمون</h2>
                <button
                    type="button"
                    class="focus-ring inline-flex min-h-11 items-center justify-center gap-2 rounded-lg btn-primary px-4 text-sm font-semibold transition duration-150 ease-standard"
                    @click="openCreateModal"
                >
                    + إضافة مستخدم
                </button>
            </div>
        </template>

        <div class="mx-auto max-w-7xl">
            <DataTable
                :columns="columns"
                :rows="users.data"
                :current-page="users.current_page"
                :last-page="users.last_page"
                :total="users.total"
                @search="onSearch"
                @update:page="onPageChange"
            >
                <template #cell-roles="{ row }">
                    {{ roleLabel((row as unknown as UserPageModel).roles[0]?.name) }}
                </template>
                <template #cell-status="{ row }">
                    <StatusBadge :status="(row as unknown as UserPageModel).status ?? 'active'" />
                </template>
                <template #cell-actions="{ row }">
                    <div class="flex flex-wrap items-center gap-x-4 gap-y-2">
                        <button type="button" class="link" @click="openEditModal(row as unknown as UserPageModel)">
                            تعديل
                        </button>
                        <button type="button" class="link-danger" @click="confirmDelete(row as unknown as UserPageModel)">
                            حذف
                        </button>
                    </div>
                </template>
            </DataTable>
        </div>

        <Modal :show="showModal" @close="closeModal()">
            <form class="p-6" @submit.prevent="submit">
                <h3 class="mb-4 text-lg font-semibold text-content">
                    {{ editingUser ? 'تعديل مستخدم' : 'إضافة مستخدم جديد' }}
                </h3>
                <div class="space-y-4">
                    <FormInput id="user-name" v-model="form.name" label="الاسم" required :error="errors.name" />
                    <FormInput id="user-email" v-model="form.email" type="email" label="البريد الإلكتروني" required :error="errors.email" />
                    <FormInput
                        id="user-password"
                        v-model="form.password"
                        type="password"
                        :label="editingUser ? 'كلمة المرور (اتركها فارغة لعدم التغيير)' : 'كلمة المرور'"
                        :required="!editingUser"
                        :error="errors.password"
                    />
                    <FormInput id="user-phone" v-model="form.phone" label="الهاتف" :error="errors.phone" />
                    <FormSelect id="user-role" v-model="form.role" label="الدور" :options="roleOptions" required :error="errors.role" />
                    <FormSelect id="user-status" v-model="form.status" label="الحالة" :options="statusOptions" required :error="errors.status" />
                </div>
                <div class="mt-6 flex justify-end gap-3">
                    <button type="button" class="focus-ring inline-flex min-h-11 items-center justify-center gap-2 rounded-lg btn-secondary px-4 text-sm font-medium transition duration-150 ease-standard" @click="closeModal()">
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
            title="حذف المستخدم"
            message="هل أنت متأكد من حذف هذا المستخدم؟"
            danger
            @confirm="deleteUser"
            @cancel="cancelDelete()"
        />
    </AuthenticatedLayout>
</template>
