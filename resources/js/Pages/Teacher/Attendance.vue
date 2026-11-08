<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import DataTable, { type DataTableColumn } from '@/Components/DataTable.vue';
import type { PaginatedResponse, TeacherAttendance } from '@/Types';

const props = defineProps<{
    attendances: PaginatedResponse<TeacherAttendance>;
}>();

const columns: DataTableColumn[] = [
    { key: 'date', label: 'التاريخ' },
    { key: 'first_activity_at', label: 'أول نشاط' },
    { key: 'last_activity_at', label: 'آخر نشاط' },
    { key: 'actions_count', label: 'عدد الأنشطة' },
];

function onPageChange(page: number): void {
    router.get(route('teacher.attendance.page'), { page }, { preserveState: true });
}
</script>

<template>
    <Head title="سجل الحضور" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-content-strong">سجل الحضور</h2>
        </template>

        <div class="mx-auto max-w-4xl">
            <p class="mb-4 text-sm text-content-muted">
                يُحسب الحضور تلقائياً بناءً على نشاطك الفعلي في النظام (تسجيل تسميعات، حضور، إلخ).
            </p>

            <DataTable
                :columns="columns"
                :rows="attendances.data"
                :current-page="attendances.current_page"
                :last-page="attendances.last_page"
                :total="attendances.total"
                :searchable="false"
                @update:page="onPageChange"
            />
        </div>
    </AuthenticatedLayout>
</template>
