<script setup lang="ts">
import AppIcon, { type IconName } from '@/Components/AppIcon.vue';

/**
 * EmptyState — الحالة الفارغة الموحّدة لكل القوائم والجداول.
 * الأيقونة اختيارية وتُعرض داخل دائرة هادئة بدل رمادي مسطّح، والنص يبقى
 * هو ما يوصّل الرسالة (لا نعتمد على اللون).
 */
withDefaults(
    defineProps<{
        title: string;
        description?: string;
        icon?: IconName;
    }>(),
    {
        description: '',
        icon: 'file-text',
    },
);
</script>

<template>
    <div class="flex flex-col items-center justify-center px-4 py-10 text-center sm:py-12">
        <span
            class="mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-surface-sunken text-content-faint"
        >
            <slot name="icon">
                <AppIcon :name="icon" size="lg" />
            </slot>
        </span>

        <h3 class="text-base font-semibold text-content-strong">{{ title }}</h3>
        <p v-if="description" class="mt-1 max-w-sm text-sm text-content-muted">
            {{ description }}
        </p>

        <div v-if="$slots.action" class="mt-4">
            <slot name="action" />
        </div>
    </div>
</template>

