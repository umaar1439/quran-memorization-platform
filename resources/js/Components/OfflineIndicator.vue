<script setup lang="ts">
import { useNetworkStatus } from '@/Composables/useNetworkStatus';
import AppIcon from '@/Components/AppIcon.vue';

/**
 * مؤشر حالة الاتصال: يظهر فقط عند انقطاعه (غيابه هو الحالة الطبيعية، فلا
 * نستهلك مساحة الشريط بشعار دائم). الحالة تُعلَن لقارئ الشاشة بـ role=status.
 */
const { isOnline } = useNetworkStatus();
</script>

<template>
    <span
        v-if="!isOnline"
        role="status"
        class="inline-flex items-center gap-1.5 rounded-full bg-red-100 px-2.5 py-1 text-xs font-medium text-red-800 dark:bg-red-500/15 dark:text-red-200"
    >
        <AppIcon name="wifi-off" size="sm" />
        غير متصل
    </span>
</template>

