<script setup lang="ts">
import { computed } from 'vue';
import { Head, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import AppButton from '@/Components/AppButton.vue';
import AppIcon from '@/Components/AppIcon.vue';
import type { PageProps } from '@/Types';

/**
 * صفحة /dashboard العامة: لا تفترض دوراً معيناً، بل توجّه المستخدم إلى
 * لوحة دوره (إدارة/معلم/طالب/ولي أمر). كانت تعرض نصاً إنجليزياً من قالب Breeze.
 */
const page = usePage<PageProps>();

const roles = computed<string[]>(() => page.props.auth.roles ?? []);

const destination = computed<string | null>(() => {
    const has = (role: string): boolean => roles.value.includes(role);

    if (has('org-admin') || has('super-admin') || has('supervisor')) return 'admin.dashboard';
    if (has('teacher')) return 'teacher.dashboard';
    if (has('student')) return 'student.dashboard';
    if (has('parent')) return 'parent.dashboard';

    return null;
});
</script>

<template>
    <Head title="لوحة التحكم" />

    <AuthenticatedLayout>
        <template #header>
            <PageHeader title="لوحة التحكم" description="مرحباً بك في منصة تحفيظ." />
        </template>

        <div class="mx-auto max-w-xl">
            <div class="card p-6 text-center sm:p-8">
                <span
                    class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-teal-50 text-teal-700 dark:bg-teal-500/10 dark:text-teal-300"
                >
                    <AppIcon name="dashboard" size="lg" />
                </span>

                <h1 class="text-lg font-semibold text-content-strong">تم تسجيل الدخول بنجاح</h1>
                <p class="mx-auto mt-1 max-w-sm text-sm text-content-muted">
                    انتقل إلى لوحتك لمتابعة الحلقات والتسميع والتقارير.
                </p>

                <div class="mt-6 flex justify-center">
                    <AppButton v-if="destination" :href="route(destination)" size="lg">
                        الانتقال إلى لوحتي
                    </AppButton>
                    <AppButton v-else :href="route('profile.edit')" size="lg">
                        الملف الشخصي
                    </AppButton>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
