<script setup lang="ts">
import { useToast } from '@/Composables/useToast';
import AppIcon, { type IconName } from '@/Components/AppIcon.vue';

/**
 * Toast — إشعارات عابرة واحدة للتطبيق.
 * بطاقة سطحية (surface) بأيقونة ملوّنة بدل خلفية ملوّنة صلبة: نص مقروء بتباين
 * AA في الوضعين، والنوع يُوصَّل بالأيقونة + كلمة مقروءة لقارئ الشاشة.
 * الموضع: فوق شريط التنقل السفلي على الجوال (في متناول الإبهام)، وأعلى
 * النافذة يمين الشاشة على الحاسب.
 */
const { toasts, dismiss } = useToast();

const typeConfig: Record<string, { icon: IconName; tone: string; label: string }> = {
    success: { icon: 'check-circle', tone: 'text-green-700 dark:text-green-300', label: 'نجاح' },
    error: { icon: 'x-circle', tone: 'text-red-700 dark:text-red-300', label: 'خطأ' },
    info: { icon: 'info', tone: 'text-blue-700 dark:text-blue-300', label: 'معلومة' },
    warning: { icon: 'alert-triangle', tone: 'text-amber-700 dark:text-amber-300', label: 'تنبيه' },
};
</script>

<template>
    <div
        class="pointer-events-none fixed inset-x-0 z-toast mx-auto flex w-full max-w-sm flex-col gap-2 px-4 above-tabbar md:inset-x-auto md:bottom-auto md:end-4 md:top-4 md:mx-0 md:w-96 md:px-0"
    >
        <transition-group name="toast" tag="div" class="flex w-full flex-col gap-2">
            <div
                v-for="toast in toasts"
                :key="toast.id"
                role="status"
                aria-live="polite"
                class="card pointer-events-auto flex items-start gap-3 p-3 shadow-raised"
            >
                <AppIcon :name="typeConfig[toast.type].icon" class="mt-0.5" :class="typeConfig[toast.type].tone" />
                <p class="min-w-0 flex-1 break-words text-sm font-medium text-content">
                    <span class="sr-only">{{ typeConfig[toast.type].label }}: </span>{{ toast.message }}
                </p>
                <button
                    type="button"
                    class="focus-ring -me-1 -mt-1 flex h-9 w-9 shrink-0 items-center justify-center rounded-lg text-content-muted hover:bg-surface-sunken hover:text-content-body"
                    aria-label="إغلاق الإشعار"
                    @click="dismiss(toast.id)"
                >
                    <AppIcon name="close" size="sm" />
                </button>
            </div>
        </transition-group>
    </div>
</template>

<style scoped>
.toast-enter-active,
.toast-leave-active {
    transition:
        opacity 0.2s cubic-bezier(0.2, 0, 0, 1),
        transform 0.2s cubic-bezier(0.2, 0, 0, 1);
}

.toast-enter-from,
.toast-leave-to {
    opacity: 0;
    transform: translateY(8px);
}
</style>

