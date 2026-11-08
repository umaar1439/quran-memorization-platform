<script setup lang="ts">
import { reactive } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import EmptyState from '@/Components/EmptyState.vue';
import { useToast } from '@/Composables/useToast';
import type { Setting } from '@/Types';

const props = defineProps<{
    settings: Setting[];
}>();

const toast = useToast();

const groups = reactive<Record<string, Setting[]>>(
    props.settings.reduce((acc: Record<string, Setting[]>, setting) => {
        const group = setting.group ?? 'عام';
        acc[group] = acc[group] ?? [];
        acc[group].push(setting);
        return acc;
    }, {}),
);

const form = useForm({
    settings: props.settings.map((s) => ({ key: s.key, value: s.value, group: s.group })),
});

function updateValue(key: string, value: string): void {
    const item = form.settings.find((s) => s.key === key);
    if (item) {
        item.value = value;
    }
}

function submit(): void {
    form.patch(route('admin.settings.update'), {
        onSuccess: () => toast.success('تم حفظ الإعدادات بنجاح.'),
    });
}
</script>

<template>
    <Head title="الإعدادات" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-content-strong">إعدادات المنظمة</h2>
        </template>

        <div class="mx-auto max-w-4xl">
            <EmptyState v-if="settings.length === 0" title="لا توجد إعدادات مُعرَّفة حالياً" />

            <form v-else class="space-y-6" @submit.prevent="submit">
                <div v-for="(items, group) in groups" :key="group" class="rounded-lg bg-surface p-6 shadow-sm">
                    <h3 class="mb-4 text-base font-semibold text-content">{{ group }}</h3>
                    <div class="space-y-4">
                        <div v-for="setting in items" :key="setting.key">
                            <label :for="`setting-${setting.key}`" class="mb-1 block text-sm font-medium text-content-body">
                                {{ setting.key }}
                            </label>
                            <input
                                :id="`setting-${setting.key}`"
                                type="text"
                                :value="setting.value"
                                class="block min-h-[44px] w-full rounded-md border-edge-strong bg-surface text-content shadow-sm focus:border-teal-500 focus:ring-teal-500 sm:text-sm"
                                @input="updateValue(setting.key, ($event.target as HTMLInputElement).value)"
                            />
                        </div>
                    </div>
                </div>

                <div class="flex justify-end">
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="min-h-[44px] rounded-md bg-teal-600 px-6 py-2 text-sm font-medium text-white disabled:opacity-50"
                    >
                        حفظ الإعدادات
                    </button>
                </div>
            </form>
        </div>
    </AuthenticatedLayout>
</template>
