<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import AppIcon from '@/Components/AppIcon.vue';

/**
 * PageHeader — عنوان الصفحة + وصف + إجراءات، داخل خانة #header في التخطيط.
 * على الجوال تتراكم الإجراءات تحت العنوان (بدل أن تزاحمه أو تُقتطع)،
 * وعلى الشاشات الأكبر تصطف بجانبه.
 */
withDefaults(
    defineProps<{
        title: string;
        description?: string;
        backHref?: string | null;
        backLabel?: string;
    }>(),
    {
        description: '',
        backHref: null,
        backLabel: 'رجوع',
    },
);
</script>

<template>
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div class="min-w-0">
            <Link
                v-if="backHref"
                :href="backHref"
                class="focus-ring mb-1 inline-flex items-center gap-1 rounded-sm text-sm font-medium text-teal-700 hover:underline dark:text-teal-300"
            >
                <!-- السهم يُعكس تلقائياً في RTL ليشير إلى اتجاه الرجوع الصحيح -->
                <AppIcon name="arrow-start" size="sm" class="rtl:rotate-180" />
                {{ backLabel }}
            </Link>

            <div class="flex flex-wrap items-center gap-x-3 gap-y-1">
                <h2 class="text-lg font-semibold leading-tight text-content-strong sm:text-xl">
                    {{ title }}
                </h2>
                <slot name="meta" />
            </div>

            <p v-if="description" class="mt-1 max-w-2xl text-sm text-content-muted">
                {{ description }}
            </p>
        </div>

        <div v-if="$slots.actions" class="flex flex-wrap items-center gap-2 sm:justify-end">
            <slot name="actions" />
        </div>
    </div>
</template>
