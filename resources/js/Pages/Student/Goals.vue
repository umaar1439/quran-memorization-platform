<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import DataTable, { type DataTableColumn } from '@/Components/DataTable.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import { useStudentGoals } from '@/Composables/useStudentGoals';
import type { StudentGoalRow } from '@/Composables/useStudentGoals';
import type { StudentGoalsPageProps } from '@/Types/api/goal';

/**
 * صفحة عرض فقط: تحويل الصفوف والتسميات والترقيم في
 * useStudentGoals/StudentGoalApi.
 */
const props = defineProps<StudentGoalsPageProps>();

const { rows, statusLabels, onPageChange } = useStudentGoals(props);

const columns: DataTableColumn[] = [
    { key: 'surah', label: 'السورة / الجزء' },
    { key: 'target_type', label: 'النوع' },
    { key: 'period_type', label: 'الفترة' },
    { key: 'target_amount', label: 'المستهدف' },
    { key: 'achieved_amount', label: 'المُنجز' },
    { key: 'completion_percentage', label: 'الإنجاز' },
    { key: 'status', label: 'الحالة' },
];
</script>

<template>
    <Head title="أهدافي" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-lg font-semibold leading-tight text-content-strong sm:text-xl">أهدافي</h2>
        </template>

        <div class="mx-auto max-w-6xl">
            <DataTable
                :columns="columns"
                :rows="rows"
                :current-page="goals.current_page"
                :last-page="goals.last_page"
                :total="goals.total"
                :searchable="false"
                @update:page="onPageChange"
            >
                <template #cell-status="{ row }">
                    <StatusBadge :status="(row as StudentGoalRow).status" :labels="statusLabels" />
                </template>
            </DataTable>
        </div>
    </AuthenticatedLayout>
</template>

