<script setup lang="ts">
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import AppIcon, { type IconName } from '@/Components/AppIcon.vue';

/**
 * AppButton — الزر الوحيد في التطبيق (Variants بدل تكرار الأصناف).
 * بديل PrimaryButton/SecondaryButton/DangerButton القديمة في Breeze.
 * - primary: إجراء رئيسي واحد لكل شاشة (تباين AA: أبيض على teal-700 = 4.66:1).
 * - secondary: إجراء ثانوي بإطار. ghost: إجراء خفيف. danger/danger-ghost: إتلافي.
 * - href: يجعل الزر رابط Inertia (نفس الشكل والسلوك البصري).
 * ارتفاع 44px كحد أدنى في الحجم md/lg للجوال؛ sm للاستخدام داخل الجداول على الحاسب.
 */
const props = withDefaults(
    defineProps<{
        variant?: 'primary' | 'secondary' | 'ghost' | 'danger' | 'danger-ghost';
        size?: 'sm' | 'md' | 'lg';
        type?: 'button' | 'submit' | 'reset';
        disabled?: boolean;
        loading?: boolean;
        block?: boolean;
        icon?: IconName | null;
        href?: string | null;
    }>(),
    {
        variant: 'primary',
        size: 'md',
        type: 'button',
        disabled: false,
        loading: false,
        block: false,
        icon: null,
        href: null,
    },
);

/* الأنماط اللونية للأزرار تأتي من أصناف CSS المشتركة (.btn-*) حتى لا تتكرر هنا */
const variantClasses: Record<string, string> = {
    primary: 'btn-primary',
    secondary: 'btn-secondary',
    ghost: 'btn-ghost',
    danger: 'btn-danger',
    'danger-ghost': 'btn-danger-ghost',
};

const sizeClasses: Record<string, string> = {
    sm: 'min-h-9 gap-1.5 px-2.5 text-sm sm:px-3',
    md: 'min-h-11 gap-2 px-4 text-sm',
    lg: 'min-h-12 gap-2 px-5 text-base',
};

const classes = computed(() => [
    'focus-ring inline-flex items-center justify-center rounded-lg font-semibold transition duration-150 ease-standard disabled:pointer-events-none disabled:opacity-50',
    variantClasses[props.variant],
    sizeClasses[props.size],
    props.block ? 'w-full' : '',
]);
</script>

<template>
    <Link v-if="href" :href="href" :class="classes" :aria-busy="loading || undefined">
        <span
            v-if="loading"
            class="h-4 w-4 animate-spin rounded-full border-2 border-current border-t-transparent"
            aria-hidden="true"
        />
        <AppIcon v-else-if="icon" :name="icon" size="sm" />
        <span v-if="$slots.default" class="truncate"><slot /></span>
    </Link>
    <button v-else :type="type" :disabled="disabled || loading" :aria-busy="loading || undefined" :class="classes">
        <span
            v-if="loading"
            class="h-4 w-4 animate-spin rounded-full border-2 border-current border-t-transparent"
            aria-hidden="true"
        />
        <AppIcon v-else-if="icon" :name="icon" size="sm" />
        <span v-if="$slots.default" class="truncate"><slot /></span>
    </button>
</template>
