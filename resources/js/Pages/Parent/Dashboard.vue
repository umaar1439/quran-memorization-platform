<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import EmptyState from '@/Components/EmptyState.vue';
import ProgressBar from '@/Components/ProgressBar.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import type { StudentProfile } from '@/Types';

interface ChildSummary {
    student: StudentProfile;
    overall_progress: {
        memorized_ayahs: number;
        total_ayahs: number;
        percentage: number;
    };
    attendance_rate: {
        attendance_days: number;
        study_days: number;
        percentage: number;
    };
}

defineProps<{
    children: ChildSummary[];
}>();
</script>

<template>
    <Head title="أبنائي" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">أبنائي</h2>
        </template>

        <div class="mx-auto max-w-5xl space-y-6">
            <EmptyState
                v-if="children.length === 0"
                title="لا يوجد أبناء مرتبطون بحسابك"
                description="تواصل مع إدارة المنظمة لربط حسابك بأبنائك."
            />

            <div
                v-for="child in children"
                :key="child.student.id"
                class="rounded-lg bg-white shadow-sm"
            >
                <div class="flex items-center justify-between border-b border-gray-200 px-4 py-3">
                    <div>
                        <h3 class="text-base font-semibold text-gray-900">
                            {{ child.student.user?.name ?? 'طالب' }}
                        </h3>
                        <p class="text-sm text-gray-500">
                            {{ child.student.mosque?.name ?? '—' }}
                            <span v-if="child.student.circle?.name"> — {{ child.student.circle.name }}</span>
                        </p>
                    </div>
                    <StatusBadge :status="child.student.status" />
                </div>

                <div class="space-y-4 p-4">
                    <div>
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-gray-600">التقدم في الحفظ</span>
                            <span class="font-medium text-gray-800">
                                {{ child.overall_progress.memorized_ayahs }} /
                                {{ child.overall_progress.total_ayahs }} آية
                            </span>
                        </div>
                        <ProgressBar
                            class="mt-2"
                            :value="child.overall_progress.percentage"
                            :show-percentage="true"
                            color="teal"
                        />
                    </div>

                    <div>
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-gray-600">نسبة الحضور</span>
                            <span class="font-medium text-gray-800">
                                {{ child.attendance_rate.attendance_days }} يوم حضور
                            </span>
                        </div>
                        <ProgressBar
                            class="mt-2"
                            :value="child.attendance_rate.percentage"
                            :show-percentage="true"
                            color="blue"
                        />
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
