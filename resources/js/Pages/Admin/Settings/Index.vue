<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import EmptyState from '@/Components/EmptyState.vue';
import { useSettings } from '@/Composables/useSettings';
import type { SettingIndexPageProps } from '@/Types/api/setting';

/**
 * صفحة عرض فقط: القيم والحفظ وأخطاء التحقق في useSettings/SettingApi.
 * الوصول إليها أصلاً محكوم بـ SettingPolicy::manage على الخادم، فكل من
 * يراها يملك صلاحية الحفظ.
 */
const props = defineProps<SettingIndexPageProps>();

const { values, groups, generalError, processing, updateValue, submit } = useSettings(props);
</script>

<template>
    <Head title="الإعدادات" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-lg font-semibold leading-tight text-content-strong sm:text-xl">إعدادات المنظمة</h2>
        </template>

        <div class="mx-auto max-w-4xl">
            <EmptyState v-if="groups.length === 0" title="لا توجد إعدادات مُعرَّفة حالياً" />

            <form v-else class="space-y-6" @submit.prevent="submit">
                <p
                    v-if="generalError"
                    class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-500/30 dark:bg-red-500/10 dark:text-red-200"
                    role="alert"
                >
                    {{ generalError }}
                </p>

                <div v-for="group in groups" :key="group.name" class="card p-6">
                    <h3 class="mb-4 text-base font-semibold text-content">{{ group.name }}</h3>
                    <div class="space-y-4">
                        <div v-for="setting in group.items" :key="setting.key">
                            <label
                                :for="`setting-${setting.key}`"
                                class="mb-1.5 block text-sm font-medium text-content-body"
                            >
                                {{ setting.key }}
                            </label>
                            <input
                                :id="`setting-${setting.key}`"
                                type="text"
                                :value="values[setting.key]"
                                class="field min-h-11"
                                @input="updateValue(setting.key, ($event.target as HTMLInputElement).value)"
                            />
                        </div>
                    </div>
                </div>

                <div class="flex justify-end">
                    <button
                        type="submit"
                        :disabled="processing"
                        class="focus-ring inline-flex min-h-11 items-center justify-center gap-2 rounded-lg btn-primary px-4 text-sm font-semibold transition duration-150 ease-standard"
                    >
                        حفظ الإعدادات
                    </button>
                </div>
            </form>
        </div>
    </AuthenticatedLayout>
</template>
