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
import type { PaginatedResponse, User } from '@/Types';

interface UserRow extends User {
    roles?: { name: string }[];
}

const props = defineProps<{
    users: PaginatedResponse<UserRow>;
    filters: { search?: string; role?: string };
    availableRoles: string[];
}>();

const toast = useToast();

const roleLabels: Record<string, string> = {
    'org-admin': 'مدير المنظمة',
    supervisor: 'مشرف',
    teacher: 'معلم',
    student: 'طالب',
    parent: 'ولي أمر',
};

const columns: DataTableColumn[] = [
    { key: 'name', label: 'الاسم' },
    { key: 'email', label: 'البريد الإلكتروني' },
    { key: 'roles', label: 'الدور' },
    { key: 'status', label: 'الحالة' },
    { key: 'actions', label: 'إجراءات' },
];

const statusOptions = [
    { value: 'active', label: 'نشط' },
    { value: 'inactive', label: 'غير نشط' },
    { value: 'suspended', label: 'موقوف' },
];

const roleOptions = props.availableRoles.map((role) => ({ value: role, label: roleLabels[role] ?? role }));

const showModal = ref(false);
const editingUser = ref<UserRow | null>(null);
const showDeleteConfirm = ref(false);
const userToDelete = ref<UserRow | null>(null);

const form = useForm({
    name: '',
    email: '',
    password: '',
    phone: '',
    role: '',
    status: 'active',
});

function openCreateModal(): void {
    editingUser.value = null;
    form.reset();
    showModal.value = true;
}

function openEditModal(user: UserRow): void {
    editingUser.value = user;
    form.name = user.name;
    form.email = user.email;
    form.password = '';
    form.phone = user.phone ?? '';
    form.role = user.roles?.[0]?.name ?? '';
    form.status = user.status ?? 'active';
    showModal.value = true;
}

function submit(): void {
    if (editingUser.value) {
        form.patch(route('admin.users.update', editingUser.value.id), {
            onSuccess: () => {
                showModal.value = false;
                toast.success('تم تحديث بيانات المستخدم بنجاح.');
            },
        });
    } else {
        form.post(route('admin.users.store'), {
            onSuccess: () => {
                showModal.value = false;
                toast.success('تم إضافة المستخدم بنجاح.');
            },
        });
    }
}

function confirmDelete(user: UserRow): void {
    userToDelete.value = user;
    showDeleteConfirm.value = true;
}

function deleteUser(): void {
    if (!userToDelete.value) return;

    router.delete(route('admin.users.destroy', userToDelete.value.id), {
        onSuccess: () => {
            showDeleteConfirm.value = false;
            toast.success('تم حذف المستخدم بنجاح.');
        },
    });
}

function onSearch(value: string): void {
    router.get(route('admin.users.index'), { ...props.filters, search: value }, { preserveState: true, replace: true });
}

function onPageChange(page: number): void {
    router.get(route('admin.users.index'), { ...props.filters, page }, { preserveState: true });
}
</script>

<template>
    <Head title="المستخدمون" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">المستخدمون</h2>
                <button
                    type="button"
                    class="min-h-[44px] rounded-md bg-teal-600 px-4 py-2 text-sm font-medium text-white hover:bg-teal-700"
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
                    {{ roleLabels[(row as unknown as UserRow).roles?.[0]?.name ?? ''] ?? '—' }}
                </template>
                <template #cell-status="{ row }">
                    <StatusBadge :status="(row as unknown as UserRow).status ?? 'active'" />
                </template>
                <template #cell-actions="{ row }">
                    <div class="flex gap-2">
                        <button type="button" class="text-sm font-medium text-teal-600 hover:underline" @click="openEditModal(row as unknown as UserRow)">
                            تعديل
                        </button>
                        <button type="button" class="text-sm font-medium text-red-600 hover:underline" @click="confirmDelete(row as unknown as UserRow)">
                            حذف
                        </button>
                    </div>
                </template>
            </DataTable>
        </div>

        <Modal :show="showModal" @close="showModal = false">
            <form class="p-6" @submit.prevent="submit">
                <h3 class="mb-4 text-lg font-semibold text-gray-900">
                    {{ editingUser ? 'تعديل مستخدم' : 'إضافة مستخدم جديد' }}
                </h3>
                <div class="space-y-4">
                    <FormInput id="user-name" v-model="form.name" label="الاسم" required :error="form.errors.name" />
                    <FormInput id="user-email" v-model="form.email" type="email" label="البريد الإلكتروني" required :error="form.errors.email" />
                    <FormInput
                        id="user-password"
                        v-model="form.password"
                        type="password"
                        :label="editingUser ? 'كلمة المرور (اتركها فارغة لعدم التغيير)' : 'كلمة المرور'"
                        :required="!editingUser"
                        :error="form.errors.password"
                    />
                    <FormInput id="user-phone" v-model="form.phone" label="الهاتف" :error="form.errors.phone" />
                    <FormSelect id="user-role" v-model="form.role" label="الدور" :options="roleOptions" required :error="form.errors.role" />
                    <FormSelect id="user-status" v-model="form.status" label="الحالة" :options="statusOptions" required :error="form.errors.status" />
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
            title="حذف المستخدم"
            message="هل أنت متأكد من حذف هذا المستخدم؟"
            danger
            @confirm="deleteUser"
            @cancel="showDeleteConfirm = false"
        />
    </AuthenticatedLayout>
</template>
