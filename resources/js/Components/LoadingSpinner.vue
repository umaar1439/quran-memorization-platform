<script setup lang="ts">
withDefaults(
    defineProps<{
        size?: 'sm' | 'md' | 'lg';
    }>(),
    {
        size: 'md',
    },
);

const sizeClasses: Record<string, string> = {
    sm: 'h-4 w-4',
    md: 'h-8 w-8',
    lg: 'h-12 w-12',
};
</script>

<template>
    <div class="flex items-center justify-center" role="status">
        <svg
            :class="sizeClasses[size]"
            class="animate-spin text-teal-600"
            fill="none"
            viewBox="0 0 24 24"
        >
            <circle
                class="opacity-25"
                cx="12"
                cy="12"
                r="10"
                stroke="currentColor"
                stroke-width="4"
            />
            <path
                class="opacity-75"
                fill="currentColor"
                d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"
            />
        </svg>
        <span class="sr-only">جارٍ التحميل...</span>
    </div>
</template>
