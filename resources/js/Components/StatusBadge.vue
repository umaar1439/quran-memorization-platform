<script setup lang="ts">
import { computed } from 'vue';

const props = defineProps<{
    status: string;
    /** خريطة تسمية اختيارية لعرض نص عربي بدل قيمة الـ enum الخام */
    labels?: Record<string, string>;
}>();

const colorMap: Record<string, string> = {
    active: 'bg-green-100 text-green-800',
    approved: 'bg-green-100 text-green-800',
    completed: 'bg-green-100 text-green-800',
    issued: 'bg-green-100 text-green-800',
    excellent: 'bg-green-100 text-green-800',
    present: 'bg-green-100 text-green-800',
    synced: 'bg-green-100 text-green-800',

    pending: 'bg-yellow-100 text-yellow-800',
    queued: 'bg-yellow-100 text-yellow-800',
    processing: 'bg-yellow-100 text-yellow-800',
    draft: 'bg-yellow-100 text-yellow-800',
    submitted: 'bg-yellow-100 text-yellow-800',
    scheduled: 'bg-yellow-100 text-yellow-800',
    trial: 'bg-yellow-100 text-yellow-800',
    late: 'bg-yellow-100 text-yellow-800',
    syncing: 'bg-yellow-100 text-yellow-800',
    conflict: 'bg-yellow-100 text-yellow-800',

    inactive: 'bg-gray-100 text-gray-700',
    withdrawn: 'bg-gray-100 text-gray-700',
    excused: 'bg-gray-100 text-gray-700',

    suspended: 'bg-red-100 text-red-800',
    rejected: 'bg-red-100 text-red-800',
    failed: 'bg-red-100 text-red-800',
    revoked: 'bg-red-100 text-red-800',
    absent: 'bg-red-100 text-red-800',
    needs_work: 'bg-red-100 text-red-800',
    cancelled: 'bg-red-100 text-red-800',

    transferred: 'bg-blue-100 text-blue-800',
    graduated: 'bg-blue-100 text-blue-800',
    good: 'bg-blue-100 text-blue-800',
    very_good: 'bg-teal-100 text-teal-800',
};

const classes = computed(() => colorMap[props.status] ?? 'bg-gray-100 text-gray-700');
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
