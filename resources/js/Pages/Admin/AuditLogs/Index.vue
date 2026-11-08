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
            <h2 class="text-lg font-semibold leading-tight text-content-strong sm:text-xl">سجل التدقيق</h2>
        </template>

        <div class="mx-auto max-w-7xl space-y-4">
            <div class="grid grid-cols-1 gap-4 card p-4 sm:grid-cols-4">
                <FormInput id="filter-event" v-model="filterForm.event" label="الحدث" placeholder="مثال: created" />
                <FormInput id="filter-from" v-model="filterForm.from" type="date" label="من تاريخ" />
                <FormInput id="filter-to" v-model="filterForm.to" type="date" label="إلى تاريخ" />
                <div class="flex items-end">
                    <button
                        type="button"
                        class="focus-ring inline-flex min-h-11 items-center justify-center gap-2 rounded-lg btn-primary px-4 text-sm font-semibold transition duration-150 ease-standard w-full"
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
