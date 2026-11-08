<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import EmptyState from '@/Components/EmptyState.vue';
import { useToast } from '@/Composables/useToast';
import type { FeatureFlag } from '@/Types';

defineProps<{
    flags: FeatureFlag[];
    isSuperAdmin: boolean;
}>();

const toast = useToast();

function toggle(flag: FeatureFlag): void {
    router.patch(
        route('admin.feature-flags.update', flag.id),
        { enabled: !flag.enabled },
        {
            preserveScroll: true,
            onSuccess: () => toast.success('تم تحديث حالة الميزة بنجاح.'),
        },
    );
}
</script>

<template>
    <Head title="أعلام الميزات" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-lg font-semibold leading-tight text-content-strong sm:text-xl">أعلام الميزات (Feature Flags)</h2>
        </template>

        <div class="mx-auto max-w-4xl">
            <EmptyState v-if="flags.length === 0" title="لا توجد أعلام ميزات متاحة" />

            <div v-else class="overflow-hidden card">
                <ul class="divide-y divide-edge-soft">
                    <li v-for="flag in flags" :key="flag.id" class="flex flex-wrap items-center justify-between gap-x-3 gap-y-1 px-4 py-4">
                        <div>
                            <p class="font-medium text-content-strong">{{ flag.key }}</p>
                            <p v-if="flag.organization_id === null" class="text-xs text-content-muted">إعداد عالمي افتراضي</p>
                        </div>
                        <button
                            type="button"
                            role="switch"
                            :aria-checked="flag.enabled"
                            class="relative inline-flex h-7 w-12 items-center rounded-full transition-colors"
                            :class="flag.enabled ? 'bg-teal-600' : 'bg-edge-strong'"
                            @click="toggle(flag)"
                        >
                            <span
                                class="inline-block h-5 w-5 transform rounded-full bg-surface transition-transform"
                                :class="flag.enabled ? '-translate-x-1' : '-translate-x-6'"
                            />
                        </button>
                    </li>
                </ul>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
