<script setup lang="ts">
import Modal from '@/Components/Modal.vue';

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
    <Modal :show="show" max-width="sm" @close="emit('cancel')">
        <div class="p-6">
            <h3 class="text-lg font-semibold text-content">{{ title }}</h3>
            <p class="mt-2 text-sm text-content-soft">{{ message }}</p>
        </div>
        <div class="flex justify-end gap-3 bg-surface-muted px-6 py-4">
            <button
                type="button"
                class="min-h-[44px] rounded-md border border-edge-strong bg-surface px-4 py-2 text-sm font-medium text-content-body hover:bg-surface-muted"
                @click="emit('cancel')"
            >
                {{ cancelText }}
            </button>
            <button
                type="button"
                :disabled="processing"
                class="min-h-[44px] rounded-md px-4 py-2 text-sm font-medium text-white disabled:opacity-50"
                :class="danger ? 'bg-red-600 hover:bg-red-700' : 'bg-teal-600 hover:bg-teal-700'"
                @click="emit('confirm')"
            >
                {{ confirmText }}
            </button>
        </div>
    </Modal>
</template>
