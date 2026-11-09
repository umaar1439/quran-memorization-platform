<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import AttendanceSheet from '@/Components/AttendanceSheet.vue';
import { useAttendanceSheet } from '@/Composables/useAttendanceSheet';
import type { AttendanceSheetPageProps } from '@/Types/api/attendance';

/**
 * صفحة عرض فقط: الحالة والحفظ والتنقل كلها في useAttendanceSheet/AttendanceApi.
 */
const props = defineProps<AttendanceSheetPageProps>();

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
} = useAttendanceSheet(props);
</script>

<template>
    <Head title="حضور الطلاب" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-lg font-semibold leading-tight text-content-strong sm:text-xl">حضور الطلاب</h2>
        </template>

        <div class="mx-auto max-w-4xl">
            <p class="mb-4 text-sm text-content-muted">
                حدّد حالة كل طالب ثم احفظ. الحفظ يُصحّح سجل اليوم القائم بدل إنشاء نسخة
                ثانية، والطالب الذي لم تُحدَّد حالته يبقى بلا سجل.
            </p>

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
        </div>
    </AuthenticatedLayout>
</template>
