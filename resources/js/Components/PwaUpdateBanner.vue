<script setup lang="ts">
import AppButton from '@/Components/AppButton.vue';
import { useServiceWorkerUpdate } from '@/Composables/useServiceWorker';

/**
 * شريط "إصدار جديد" — يظهر فقط عند وجود Service Worker في حالة waiting.
 * لا يُعيد التحميل تلقائياً حفاظاً على أي إدخال غير محفوظ (نماذج/تسميع).
 * الموضع: فوق شريط التنقل السفلي على الجوال، وأسفل نهاية الشاشة على الحاسب.
 */
const { updateReady, isApplying, applyUpdate } = useServiceWorkerUpdate();
</script>

<template>
    <div
        v-if="updateReady"
        role="status"
        aria-live="polite"
        class="fixed start-4 end-4 bottom-[calc(4.5rem+env(safe-area-inset-bottom,0px))] z-toast mx-auto flex max-w-md items-center justify-between gap-3 rounded-xl border border-edge bg-surface p-3 shadow-pop md:start-auto md:bottom-4 md:mx-0"
    >
        <p class="min-w-0 text-sm font-medium text-content">يتوفر إصدار جديد من التطبيق.</p>
        <AppButton :loading="isApplying" @click="applyUpdate">تحديث</AppButton>
    </div>
</template>
