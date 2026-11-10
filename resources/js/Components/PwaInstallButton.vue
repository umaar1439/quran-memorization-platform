<script setup lang="ts">
import AppIcon from '@/Components/AppIcon.vue';
import { useInstallPrompt } from '@/Composables/useInstallPrompt';

/**
 * زر تثبيت التطبيق — يظهر فقط حين يوفّر المتصفح beforeinstallprompt
 * (Chrome/Edge على Android وسطح المكتب). لا وجود لزر معطّل على iOS/Safari
 * لأن التثبيت هناك يدوي من قائمة المشاركة.
 */
const { canInstall, promptInstall } = useInstallPrompt();
</script>

<template>
    <button
        v-if="canInstall"
        type="button"
        class="focus-ring inline-flex h-11 min-w-11 items-center justify-center gap-2 rounded-lg px-2 text-sm font-medium text-content-body transition-colors duration-150 ease-standard hover:bg-surface-sunken"
        aria-label="تثبيت التطبيق على هذا الجهاز"
        title="تثبيت التطبيق"
        @click="promptInstall"
    >
        <AppIcon name="download" />
        <span class="hidden lg:inline">تثبيت</span>
    </button>
</template>
