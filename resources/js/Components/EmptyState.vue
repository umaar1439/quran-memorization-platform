<script setup lang="ts">
withDefaults(
    defineProps<{
        title: string;
        description?: string;
    }>(),
    {
        description: '',
    },
);
</script>

<template>
    <div class="flex flex-col items-center justify-center px-4 py-12 text-center">
        <div class="mb-4 text-gray-400">
            <slot name="icon">
                <svg class="h-16 w-16" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="1.5"
                        d="M9 13h6m-6 4h6m2 5H7a2 2 0 01-2-2V4a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V20a2 2 0 01-2 2z"
                    />
                </svg>
            </slot>
        </div>
        <h3 class="text-base font-semibold text-gray-900">{{ title }}</h3>
        <p v-if="description" class="mt-1 text-sm text-gray-500">
            {{ description }}
        </p>
        <div v-if="$slots.action" class="mt-4">
            <slot name="action" />
        </div>
    </div>
</template>
