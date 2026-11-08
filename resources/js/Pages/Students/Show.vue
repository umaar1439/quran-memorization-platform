<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import StudentAnalyticsPanel from '@/Components/StudentAnalytics.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import type { StudentAnalytics, StudentProfile } from '@/Types';

defineProps<{
    student: StudentProfile;
    analytics: StudentAnalytics;
}>();

function goBack(): void {
    window.history.back();
}
</script>

<template>
    <Head :title="`ملف الطالب ${student.user?.name ?? ''}`" />

    <AuthenticatedLayout>
        <template #header>
            <div>
                <button
                    type="button"
                    class="text-sm font-medium text-teal-600 dark:text-teal-400 hover:text-teal-700 dark:hover:text-teal-300"
                    @click="goBack"
                >
                    → رجوع
                </button>
                <h2 class="mt-1 text-xl font-semibold leading-tight text-content-strong">
                    {{ student.user?.name ?? 'طالب' }}
                </h2>
            </div>
        </template>

        <div class="mx-auto max-w-7xl space-y-6">
            <!-- معلومات الطالب -->
            <div class="rounded-lg bg-surface p-6 shadow-sm">
                <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-5">
                    <div>
                        <p class="text-sm text-content-muted">الحالة</p>
                        <div class="mt-1">
                            <StatusBadge :status="student.status" />
                        </div>
                    </div>
                    <div>
                        <p class="text-sm text-content-muted">المسجد</p>
                        <p class="mt-1 font-medium text-content-strong">{{ student.mosque?.name ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-content-muted">الحلقة</p>
                        <p class="mt-1 font-medium text-content-strong">{{ student.circle?.name ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-content-muted">الجزء الحالي</p>
                        <p class="mt-1 font-medium text-content-strong">{{ student.current_juz ? `الجزء ${student.current_juz}` : '—' }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-content-muted">رقم القيد</p>
                        <p class="mt-1 font-medium text-content-strong">{{ student.enrollment_number ?? '—' }}</p>
                    </div>
                </div>
            </div>

            <StudentAnalyticsPanel :analytics="analytics" />
        </div>
    </AuthenticatedLayout>
</template>
