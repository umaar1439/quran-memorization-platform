<script setup lang="ts">
import { useNetworkStatus } from '@/Composables/useNetworkStatus';

const { isOnline } = useNetworkStatus();
</script>

<template>
    <span
        v-if="!isOnline"
        class="inline-flex items-center gap-1.5 rounded-full bg-red-100 px-2.5 py-1 text-xs font-medium text-red-800 dark:bg-red-500/15 dark:text-red-200"
    >
        <span class="h-2 w-2 rounded-full bg-red-600" />
        غير متصل
    </span>
    <span
        v-else
        class="inline-flex items-center gap-1.5 rounded-full bg-green-100 px-2.5 py-1 text-xs font-medium text-green-800 dark:bg-green-500/15 dark:text-green-200"
    >
        <span class="h-2 w-2 rounded-full bg-green-600" />
        متصل
    </span>
</template>
