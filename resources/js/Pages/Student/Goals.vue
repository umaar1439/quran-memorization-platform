<script setup lang="ts">
import { computed } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import DataTable, { type DataTableColumn } from '@/Components/DataTable.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import type { PaginatedResponse, StudentGoal } from '@/Types';

const props = defineProps<{
    goals: PaginatedResponse<StudentGoal>;
}>();

const targetTypeLabels: Record<string, string> = {
    memorization: 'حفظ',
    revision: 'مراجعة',
};

const periodTypeLabels: Record<string, string> = {
    daily: 'يومي',
    weekly: 'أسبوعي',
    monthly: 'شهري',
};

const statusLabels: Record<string, string> = {
    active: 'نشط',
    completed: 'مكتمل',
    failed: 'فشل',
    cancelled: 'ملغي',
};

interface GoalRow {
    id: number;
    surah: string;
    target_type: string;
    period_type: string;
    target_amount: number;
    achieved_amount: number;
    completion_percentage: string;
    status: string;
}

const columns: DataTableColumn[] = [
    { key: 'surah', label: 'السورة / الجزء' },
    { key: 'target_type', label: 'النوع' },
    { key: 'period_type', label: 'الفترة' },
    { key: 'target_amount', label: 'المستهدف' },
    { key: 'achieved_amount', label: 'المُنجز' },
    { key: 'completion_percentage', label: 'الإنجاز' },
    { key: 'status', label: 'الحالة' },
];

const rows = computed(() =>
    props.goals.data.map((goal) => ({
        id: goal.id,
        surah: goal.surah?.name_ar ?? (goal.juz_number ? `الجزء ${goal.juz_number}` : '—'),
        target_type: targetTypeLabels[goal.target_type] ?? goal.target_type,
        period_type: periodTypeLabels[goal.period_type] ?? goal.period_type,
        target_amount: goal.target_amount,
        achieved_amount: goal.achieved_amount,
        completion_percentage: `${goal.completion_percentage}%`,
        status: goal.status,
    })),
);

function onPageChange(page: number): void {
    router.get(route('student.goals'), { page }, { preserveState: true });
}
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
                    <StatusBadge :status="(row as unknown as GoalRow).status" :labels="statusLabels" />
                </template>
            </DataTable>
        </div>
    </AuthenticatedLayout>
</template>
