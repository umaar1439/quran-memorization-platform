<script setup lang="ts">
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';

/**
 * BrandMark — علامة المنصة (حرف "ت" في مربع) + الاسم.
 * كانت العلامة تتكرر بثلاث نسخ مختلفة (Welcome/GuestLayout/التخطيط)، والتخطيط
 * كان يستخدم شعار Laravel الافتراضي لتطبيق قرآني. الآن نسخة واحدة.
 */
const props = withDefaults(
    defineProps<{
        label?: string;
        size?: 'sm' | 'md' | 'lg';
        href?: string | null;
    }>(),
    {
        label: 'تحفيظ',
        size: 'md',
        href: null,
    },
);

/* تمرير href فقط عندما تكون العلامة رابطاً، حتى لا يظهر href على div */
const linkAttrs = computed(() => (props.href ? { href: props.href } : {}));

const markSizeClasses: Record<string, string> = {
    sm: 'h-8 w-8 rounded-lg text-base',
    md: 'h-9 w-9 rounded-lg text-lg',
    lg: 'h-12 w-12 rounded-xl text-2xl',
};

const labelSizeClasses: Record<string, string> = {
    sm: 'text-sm',
    md: 'text-sm',
    lg: 'text-2xl',
};
</script>

<template>
    <component
        :is="href ? Link : 'div'"
        v-bind="linkAttrs"
        class="flex items-center gap-2.5"
        :class="href ? 'focus-ring rounded-lg' : ''"
    >
        <span
            class="flex shrink-0 items-center justify-center bg-teal-700 font-bold text-white dark:bg-teal-500 dark:text-teal-950"
            :class="markSizeClasses[size]"
            aria-hidden="true"
        >
            ت
        </span>
        <span class="font-bold text-content-strong" :class="labelSizeClasses[size]">{{ label }}</span>
    </component>
</template>
