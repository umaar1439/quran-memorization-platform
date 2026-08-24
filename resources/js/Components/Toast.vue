<script setup lang="ts">
import { useToast } from '@/Composables/useToast';

const { toasts, dismiss } = useToast();

const typeClasses: Record<string, string> = {
    success: 'bg-green-600',
    error: 'bg-red-600',
    info: 'bg-blue-600',
    warning: 'bg-amber-500',
};
</script>

<template>
    <div class="pointer-events-none fixed inset-x-0 top-4 z-50 flex flex-col items-center gap-2 px-4">
        <transition-group name="toast" tag="div" class="flex w-full max-w-sm flex-col gap-2">
            <div
                v-for="toast in toasts"
                :key="toast.id"
                :class="typeClasses[toast.type]"
                class="pointer-events-auto flex items-center justify-between gap-3 rounded-lg px-4 py-3 text-sm font-medium text-white shadow-lg"
            >
                <span>{{ toast.message }}</span>
                <button
                    type="button"
                    class="shrink-0 rounded p-1 text-white/80 hover:text-white"
                    @click="dismiss(toast.id)"
                >
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </transition-group>
    </div>
</template>

<style scoped>
.toast-enter-active,
.toast-leave-active {
    transition: all 0.2s ease;
}

.toast-enter-from,
.toast-leave-to {
    opacity: 0;
    transform: translateY(-8px);
}
</style>
