<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';

const props = withDefaults(
    defineProps<{
        show?: boolean;
        maxWidth?: 'sm' | 'md' | 'lg' | 'xl' | '2xl';
        closeable?: boolean;
        /** وصف النافذة لقارئ الشاشة (العنوان المرئي موجود داخل المحتوى) */
        title?: string;
    }>(),
    {
        show: false,
        maxWidth: '2xl',
        closeable: true,
        title: '',
    },
);

const emit = defineEmits(['close']);
const dialog = ref();
const showSlot = ref(props.show);

watch(
    () => props.show,
    () => {
        if (props.show) {
            document.body.style.overflow = 'hidden';
            showSlot.value = true;

            dialog.value?.showModal();
        } else {
            document.body.style.overflow = '';

            setTimeout(() => {
                dialog.value?.close();
                showSlot.value = false;
            }, 200);
        }
    },
);

const close = () => {
    if (props.closeable) {
        emit('close');
    }
};

const closeOnEscape = (e: KeyboardEvent) => {
    if (e.key === 'Escape') {
        e.preventDefault();

        if (props.show) {
            close();
        }
    }
};

onMounted(() => document.addEventListener('keydown', closeOnEscape));

onUnmounted(() => {
    document.removeEventListener('keydown', closeOnEscape);

    document.body.style.overflow = '';
});

const maxWidthClass = computed(() => {
    return {
        sm: 'sm:max-w-sm',
        md: 'sm:max-w-md',
        lg: 'sm:max-w-lg',
        xl: 'sm:max-w-xl',
        '2xl': 'sm:max-w-2xl',
    }[props.maxWidth];
});
</script>

<template>
    <dialog
        ref="dialog"
        class="z-modal m-0 h-full max-h-none w-full max-w-none overflow-y-auto bg-transparent p-0 backdrop:bg-transparent"
        aria-modal="true"
        :aria-label="title || undefined"
    >
        <div class="fixed inset-0 z-modal flex min-h-full items-end justify-center sm:items-center sm:p-6">
            <Transition
                enter-active-class="transition duration-200 ease-standard"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="transition duration-150 ease-standard"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div
                    v-show="show"
                    class="fixed inset-0 bg-scrim/50 dark:bg-scrim/70"
                    @click="close"
                />
            </Transition>

            <Transition
                enter-active-class="transition duration-200 ease-standard"
                enter-from-class="translate-y-4 opacity-0 sm:translate-y-0 sm:scale-95"
                enter-to-class="translate-y-0 opacity-100 sm:scale-100"
                leave-active-class="transition duration-150 ease-standard"
                leave-from-class="translate-y-0 opacity-100 sm:scale-100"
                leave-to-class="translate-y-4 opacity-0 sm:translate-y-0 sm:scale-95"
            >
                <!--
                 * على الجوال: ورقة سفلية (bottom sheet) بعرض كامل، فلا تُقتطع
                 * النماذج الطويلة ولا تخرج عن الشاشة. على sm+ نافذة مركزية.
                 -->
                <div
                    v-show="show"
                    class="relative w-full transform overflow-hidden rounded-t-2xl border border-edge bg-surface shadow-pop transition-all sm:rounded-2xl"
                    :class="maxWidthClass"
                >
                    <div class="max-h-[85dvh] overflow-y-auto overscroll-contain pb-safe">
                        <slot v-if="showSlot" />
                    </div>
                </div>
            </Transition>
        </div>
    </dialog>
</template>
