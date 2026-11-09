<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import DataTable, { type DataTableColumn } from '@/Components/DataTable.vue';
import FormInput from '@/Components/FormInput.vue';
import { useAuditLogs } from '@/Composables/useAuditLogs';
import type { AuditLogIndexPageProps, AuditLogPageModel } from '@/Types/api/auditLog';

/**
 * صفحة عرض فقط (قراءة بحتة): شريط الفلاتر والترقيم في
 * useAuditLogs/AuditLogApi. لا يوجد أي إجراء كتابة على سجل التدقيق.
 */
const props = defineProps<AuditLogIndexPageProps>();

const { filters, applyFilters, onPageChange } = useAuditLogs(props);

const columns: DataTableColumn[] = [
    { key: 'event', label: 'الحدث' },
    { key: 'user', label: 'المستخدم' },
    { key: 'auditable_type', label: 'النوع' },
    { key: 'ip_address', label: 'عنوان IP' },
    { key: 'created_at', label: 'التاريخ' },
];
</script>

<template>
    <Head title="سجل التدقيق" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-lg font-semibold leading-tight text-content-strong sm:text-xl">سجل التدقيق</h2>
        </template>

        <div class="mx-auto max-w-7xl space-y-4">
            <div class="card grid grid-cols-1 gap-4 p-4 sm:grid-cols-4">
                <FormInput id="filter-event" v-model="filters.event" label="الحدث" placeholder="مثال: created" />
                <FormInput id="filter-from" v-model="filters.from" type="date" label="من تاريخ" />
                <FormInput id="filter-to" v-model="filters.to" type="date" label="إلى تاريخ" />
                <div class="flex items-end">
                    <button
                        type="button"
                        class="focus-ring inline-flex min-h-11 w-full items-center justify-center gap-2 rounded-lg btn-primary px-4 text-sm font-semibold transition duration-150 ease-standard"
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
                    {{ (row as AuditLogPageModel).user?.name ?? 'النظام' }}
                </template>
                <template #cell-auditable_type="{ row }">
                    {{ (row as AuditLogPageModel).auditable_type ?? '—' }}
                </template>
                <template #cell-ip_address="{ row }">
                    {{ (row as AuditLogPageModel).ip_address ?? '—' }}
                </template>
            </DataTable>
        </div>
    </AuthenticatedLayout>
</template>
