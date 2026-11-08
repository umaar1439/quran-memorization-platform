<script setup lang="ts">
import AppIcon, { type IconName } from '@/Components/AppIcon.vue';

/**
 * StatCard — بطاقة مؤشر رقمي واحدة تُستخدم في لوحات التحكم الثلاث
 * (الإدارة، الطالب، ولي الأمر) بدل تكرار نفس البطاقة 6 مرات في كل صفحة.
 * الأرقام tabular-nums حتى لا تتزحزح عند تغيّر القيمة.
 */
withDefaults(
    defineProps<{
        label: string;
        value: string | number;
        hint?: string;
        icon?: IconName | null;
        accent?: 'neutral' | 'teal' | 'amber' | 'green' | 'blue' | 'red';
    }>(),
    {
        hint: '',
        icon: null,
        accent: 'neutral',
    },
);

const accentClasses: Record<string, { chip: string; value: string }> = {
    neutral: { chip: 'bg-surface-sunken text-content-soft', value: 'text-content' },
    teal: {
        chip: 'bg-teal-50 text-teal-700 dark:bg-teal-500/10 dark:text-teal-300',
        value: 'text-teal-700 dark:text-teal-300',
    },
    amber: {
        chip: 'bg-amber-50 text-amber-700 dark:bg-amber-500/10 dark:text-amber-300',
        value: 'text-amber-700 dark:text-amber-300',
    },
    green: {
        chip: 'bg-green-50 text-green-700 dark:bg-green-500/10 dark:text-green-300',
        value: 'text-green-700 dark:text-green-300',
    },
    blue: {
        chip: 'bg-blue-50 text-blue-700 dark:bg-blue-500/10 dark:text-blue-300',
        value: 'text-blue-700 dark:text-blue-300',
    },
    red: {
        chip: 'bg-red-50 text-red-700 dark:bg-red-500/10 dark:text-red-300',
        value: 'text-red-700 dark:text-red-300',
    },
};
</script>

<template>
    <div class="card flex items-start justify-between gap-3 p-4 sm:p-5">
        <div class="min-w-0">
            <p class="text-sm text-content-muted">{{ label }}</p>
            <p class="mt-1 text-2xl font-bold tabular-nums" :class="accentClasses[accent].value">
                {{ value }}
            </p>
            <p v-if="hint" class="mt-1 text-xs text-content-muted">{{ hint }}</p>
        </div>
        <span
            v-if="icon"
            class="hidden h-10 w-10 shrink-0 items-center justify-center rounded-lg sm:flex"
            :class="accentClasses[accent].chip"
        >
            <AppIcon :name="icon" />
        </span>
    </div>
</template>
