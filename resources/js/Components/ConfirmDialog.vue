<script setup lang="ts">
import Modal from '@/Components/Modal.vue';
import AppButton from '@/Components/AppButton.vue';
import AppIcon from '@/Components/AppIcon.vue';

/**
 * ConfirmDialog — تأكيد الإجراءات الإتلافية على Modal الموحّد.
 * على الجوال تتراكم الأزرار عمودياً (الإجراء الأساسي أقرب للإبهام)
 * وعلى الشاشات الأكبر تبقى في صف واحد.
 */
withDefaults(
    defineProps<{
        show: boolean;
        title?: string;
        message?: string;
        confirmText?: string;
        cancelText?: string;
        danger?: boolean;
        processing?: boolean;
    }>(),
    {
        title: 'تأكيد العملية',
        message: 'هل أنت متأكد من رغبتك في تنفيذ هذا الإجراء؟',
        confirmText: 'تأكيد',
        cancelText: 'إلغاء',
        danger: false,
        processing: false,
    },
);

const emit = defineEmits<{
    confirm: [];
    cancel: [];
}>();
</script>

<template>
    <Modal :show="show" max-width="md" :title="title" @close="emit('cancel')">
        <div class="flex items-start gap-3 p-6">
            <span
                class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full"
                :class="danger
                    ? 'bg-red-50 text-red-700 dark:bg-red-500/10 dark:text-red-300'
                    : 'bg-teal-50 text-teal-700 dark:bg-teal-500/10 dark:text-teal-300'"
                aria-hidden="true"
            >
                <AppIcon :name="danger ? 'alert-triangle' : 'info'" />
            </span>
            <div class="min-w-0">
                <h3 class="text-lg font-semibold text-content-strong">{{ title }}</h3>
                <p class="mt-1 text-sm text-content-soft">{{ message }}</p>
            </div>
        </div>

        <div class="flex flex-col-reverse gap-2 border-t border-edge bg-surface-muted px-6 py-4 sm:flex-row sm:justify-end">
            <AppButton variant="secondary" @click="emit('cancel')">{{ cancelText }}</AppButton>
            <AppButton
                :variant="danger ? 'danger' : 'primary'"
                :loading="processing"
                @click="emit('confirm')"
            >
                {{ confirmText }}
            </AppButton>
        </div>
    </Modal>
</template>
