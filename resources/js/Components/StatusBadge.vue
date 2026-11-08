<script setup lang="ts">
import { computed } from 'vue';

const props = defineProps<{
    status: string;
    /** خريطة تسمية اختيارية لعرض نص عربي بدل قيمة الـ enum الخام */
    labels?: Record<string, string>;
}>();

const colorMap: Record<string, string> = {
    active: 'bg-green-100 dark:bg-green-500/15 text-green-800 dark:text-green-200',
    approved: 'bg-green-100 dark:bg-green-500/15 text-green-800 dark:text-green-200',
    completed: 'bg-green-100 dark:bg-green-500/15 text-green-800 dark:text-green-200',
    issued: 'bg-green-100 dark:bg-green-500/15 text-green-800 dark:text-green-200',
    excellent: 'bg-green-100 dark:bg-green-500/15 text-green-800 dark:text-green-200',
    present: 'bg-green-100 dark:bg-green-500/15 text-green-800 dark:text-green-200',
    synced: 'bg-green-100 dark:bg-green-500/15 text-green-800 dark:text-green-200',

    pending: 'bg-yellow-100 dark:bg-yellow-500/15 text-yellow-800 dark:text-yellow-200',
    queued: 'bg-yellow-100 dark:bg-yellow-500/15 text-yellow-800 dark:text-yellow-200',
    processing: 'bg-yellow-100 dark:bg-yellow-500/15 text-yellow-800 dark:text-yellow-200',
    draft: 'bg-yellow-100 dark:bg-yellow-500/15 text-yellow-800 dark:text-yellow-200',
    submitted: 'bg-yellow-100 dark:bg-yellow-500/15 text-yellow-800 dark:text-yellow-200',
    scheduled: 'bg-yellow-100 dark:bg-yellow-500/15 text-yellow-800 dark:text-yellow-200',
    trial: 'bg-yellow-100 dark:bg-yellow-500/15 text-yellow-800 dark:text-yellow-200',
    late: 'bg-yellow-100 dark:bg-yellow-500/15 text-yellow-800 dark:text-yellow-200',
    syncing: 'bg-yellow-100 dark:bg-yellow-500/15 text-yellow-800 dark:text-yellow-200',
    conflict: 'bg-yellow-100 dark:bg-yellow-500/15 text-yellow-800 dark:text-yellow-200',

    inactive: 'bg-surface-sunken text-content-body',
    withdrawn: 'bg-surface-sunken text-content-body',
    excused: 'bg-surface-sunken text-content-body',

    suspended: 'bg-red-100 dark:bg-red-500/15 text-red-800 dark:text-red-200',
    rejected: 'bg-red-100 dark:bg-red-500/15 text-red-800 dark:text-red-200',
    failed: 'bg-red-100 dark:bg-red-500/15 text-red-800 dark:text-red-200',
    revoked: 'bg-red-100 dark:bg-red-500/15 text-red-800 dark:text-red-200',
    absent: 'bg-red-100 dark:bg-red-500/15 text-red-800 dark:text-red-200',
    needs_work: 'bg-red-100 dark:bg-red-500/15 text-red-800 dark:text-red-200',
    cancelled: 'bg-red-100 dark:bg-red-500/15 text-red-800 dark:text-red-200',

    transferred: 'bg-blue-100 dark:bg-blue-500/15 text-blue-800 dark:text-blue-200',
    graduated: 'bg-blue-100 dark:bg-blue-500/15 text-blue-800 dark:text-blue-200',
    good: 'bg-blue-100 dark:bg-blue-500/15 text-blue-800 dark:text-blue-200',
    very_good: 'bg-teal-100 dark:bg-teal-500/15 text-teal-800 dark:text-teal-200',
};

const classes = computed(() => colorMap[props.status] ?? 'bg-surface-sunken text-content-body');
const label = computed(() => props.labels?.[props.status] ?? props.status);
</script>

<template>
    <span
        :class="classes"
        class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium"
    >
        {{ label }}
    </span>
</template>
