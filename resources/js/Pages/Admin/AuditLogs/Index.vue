<script setup lang="ts">
import { reactive } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import DataTable, { type DataTableColumn } from '@/Components/DataTable.vue';
import FormInput from '@/Components/FormInput.vue';
import type { AuditLog, PaginatedResponse } from '@/Types';

const props = defineProps<{
    auditLogs: PaginatedResponse<AuditLog>;
    filters: { event?: string; user_id?: string; from?: string; to?: string };
}>();

const columns: DataTableColumn[] = [
    { key: 'event', label: 'الحدث' },
    { key: 'user', label: 'المستخدم' },
    { key: 'auditable_type', label: 'النوع' },
    { key: 'ip_address', label: 'عنوان IP' },
    { key: 'created_at', label: 'التاريخ' },
];

const filterForm = reactive({
    event: props.filters.event ?? '',
    from: props.filters.from ?? '',
    to: props.filters.to ?? '',
});

function applyFilters(): void {
    router.get(route('admin.audit-logs.page'), { ...filterForm }, { preserveState: true, replace: true });
}

function onPageChange(page: number): void {
    router.get(route('admin.audit-logs.page'), { ...filterForm, page }, { preserveState: true });
}
</script>

<template>
    <Head title="سجل التدقيق" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-content-strong">سجل التدقيق</h2>
        </template>

        <div class="mx-auto max-w-7xl space-y-4">
            <div class="grid grid-cols-1 gap-4 rounded-lg bg-surface p-4 shadow-sm sm:grid-cols-4">
                <FormInput id="filter-event" v-model="filterForm.event" label="الحدث" placeholder="مثال: created" />
                <FormInput id="filter-from" v-model="filterForm.from" type="date" label="من تاريخ" />
                <FormInput id="filter-to" v-model="filterForm.to" type="date" label="إلى تاريخ" />
                <div class="flex items-end">
                    <button
                        type="button"
                        class="min-h-[44px] w-full rounded-md bg-teal-600 px-4 py-2 text-sm font-medium text-white hover:bg-teal-700"
                        @click="applyFilters"
                    >
                        تصفية
                    </button>
                </div>
            </div>

            <DataTable
                :columns="columns"
                :rows="auditLogs.data"
                :current-page="auditLogs.current_page"
                :last-page="auditLogs.last_page"
                :total="auditLogs.total"
                :searchable="false"
                @update:page="onPageChange"
            >
                <template #cell-user="{ row }">
                    {{ (row as unknown as AuditLog).user?.name ?? 'النظام' }}
                </template>
            </DataTable>
        </div>
    </AuthenticatedLayout>
</template>
