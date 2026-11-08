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
            <h2 class="text-lg font-semibold leading-tight text-content-strong sm:text-xl">إعدادات المنظمة</h2>
        </template>

        <div class="mx-auto max-w-4xl">
            <EmptyState v-if="settings.length === 0" title="لا توجد إعدادات مُعرَّفة حالياً" />

            <form v-else class="space-y-6" @submit.prevent="submit">
                <div v-for="(items, group) in groups" :key="group" class="card p-6">
                    <h3 class="mb-4 text-base font-semibold text-content">{{ group }}</h3>
                    <div class="space-y-4">
                        <div v-for="setting in items" :key="setting.key">
                            <label :for="`setting-${setting.key}`" class="mb-1.5 block text-sm font-medium text-content-body">
                                {{ setting.key }}
                            </label>
                            <input
                                :id="`setting-${setting.key}`"
                                type="text"
                                :value="setting.value"
                                class="field min-h-11"
                                @input="updateValue(setting.key, ($event.target as HTMLInputElement).value)"
                            />
                        </div>
                    </div>
                </div>

                <div class="flex justify-end">
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="focus-ring inline-flex min-h-11 items-center justify-center gap-2 rounded-lg btn-primary px-4 text-sm font-semibold transition duration-150 ease-standard"
                    >
                        حفظ الإعدادات
                    </button>
                </div>
            </form>
        </div>
    </AuthenticatedLayout>
</template>
