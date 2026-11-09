<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import AttendanceSheet from '@/Components/AttendanceSheet.vue';
import DataTable, { type DataTableColumn } from '@/Components/DataTable.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import { useAttendanceSheet } from '@/Composables/useAttendanceSheet';
import { useAttendanceHistory } from '@/Composables/useAttendanceHistory';
import type { AttendanceHistoryRow, AttendanceIndexPageProps } from '@/Types/api/attendance';

/**
 * صفحة عرض فقط: الورقة اليومية (useAttendanceSheet) والسجل المفصّل
 * (useAttendanceHistory) — لا منطق عمل ولا نداءات شبكة هنا.
 */
const props = defineProps<AttendanceIndexPageProps>();

const {
    rows,
    summary,
    summaryLabels,
    statusOptions,
    markedCount,
    canManage,
    processing,
    errors,
    setStatus,
    setNote,
    markAllPresent,
    clearAll,
    save,
    onDateChange,
    onCircleChange,
} = useAttendanceSheet(props, { routeName: 'admin.attendance.index' });

const { filters, filterFields, statusLabels, onFilterChange, onDateFilterChange, onPageChange } =
    useAttendanceHistory(props);

const columns: DataTableColumn[] = [
    { key: 'date', label: 'التاريخ' },
    { key: 'student', label: 'الطالب' },
    { key: 'circle', label: 'الحلقة' },
    { key: 'status', label: 'الحالة' },
    { key: 'notes', label: 'ملاحظة' },
    { key: 'recorder', label: 'سجّله' },
];

/** cast 'date' يُسلسَل ISO 8601 كاملاً، والجدول يعرض اليوم فقط. */
function day(value: string | null): string {
    return value ? value.slice(0, 10) : '—';
}
</script>

<template>
    <Head title="حضور الطلاب" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-lg font-semibold leading-tight text-content-strong sm:text-xl">حضور الطلاب</h2>
        </template>

        <div class="mx-auto max-w-7xl space-y-6">
            <section class="card p-4 sm:p-6">
                <h3 class="mb-3 text-base font-semibold text-content-strong">ورقة الحضور اليومية</h3>
                <AttendanceSheet
                    :rows="rows"
                    :status-options="statusOptions"
                    :summary-labels="summaryLabels"
                    :summary="summary"
                    :circles="props.sheet.circles"
                    :circle-id="props.sheet.circle_id"
                    :date="props.sheet.date"
                    :marked-count="markedCount"
                    :can-manage="canManage"
                    :processing="processing"
                    :errors="errors"
                    @set-status="setStatus"
                    @set-note="setNote"
                    @mark-all="markAllPresent"
                    @clear-all="clearAll"
                    @change-date="onDateChange"
                    @change-circle="onCircleChange"
                    @save="save"
                />
            </section>

            <section class="space-y-4">
                <h3 class="text-base font-semibold text-content-strong">سجل الحضور</h3>

                <div class="card grid gap-3 p-3 sm:grid-cols-3 sm:p-4">
                    <label v-for="field in filterFields" :key="field.key" class="block min-w-0">
                        <span class="mb-1.5 block text-sm font-medium text-content-body">{{ field.label }}</span>
                        <select
                            class="field min-h-11"
                            :value="filters[field.key] ?? ''"
                            @change="onFilterChange(field.key, $event)"
                        >
                            <option value="">{{ field.allLabel }}</option>
                            <option v-for="option in field.options" :key="option.value" :value="option.value">
                                {{ option.label }}
                            </option>
                        </select>
                    </label>

                    <label class="block min-w-0">
                        <span class="mb-1.5 block text-sm font-medium text-content-body">التاريخ</span>
                        <input
                            type="date"
                            class="field min-h-11"
                            :value="filters.date ?? ''"
                            @change="onDateFilterChange"
                        />
                    </label>
                </div>

                <DataTable
                    :columns="columns"
                    :rows="attendances.data"
                    :current-page="attendances.current_page"
                    :last-page="attendances.last_page"
                    :total="attendances.total"
                    :searchable="false"
                    label="سجل الحضور"
                    @update:page="onPageChange"
                >
                    <template #cell-date="{ row }">
                        {{ day((row as AttendanceHistoryRow).date) }}
                    </template>
                    <template #cell-student="{ row }">
                        {{ (row as AttendanceHistoryRow).student?.user?.name ?? '—' }}
                    </template>
                    <template #cell-circle="{ row }">
                        {{ (row as AttendanceHistoryRow).circle?.name ?? '—' }}
                    </template>
                    <template #cell-status="{ row }">
                        <StatusBadge
                            :status="(row as AttendanceHistoryRow).status"
                            :labels="statusLabels"
                        />
                    </template>
                    <template #cell-notes="{ row }">
                        {{ (row as AttendanceHistoryRow).notes ?? '—' }}
                    </template>
                    <template #cell-recorder="{ row }">
                        {{ (row as AttendanceHistoryRow).recorder?.name ?? '—' }}
                    </template>
                </DataTable>
            </section>
        </div>
    </AuthenticatedLayout>
</template>
