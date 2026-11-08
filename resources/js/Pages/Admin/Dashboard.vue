<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import StatusBadge from '@/Components/StatusBadge.vue';
import EmptyState from '@/Components/EmptyState.vue';
import type { Recitation } from '@/Types';

interface Stats {
    mosques_count: number;
    circles_count: number;
    students_count: number;
    teachers_count: number;
    recitations_today: number;
    recitations_this_month: number;
}

defineProps<{
    stats: Stats;
    recentRecitations: Recitation[];
}>();

const ratingLabels: Record<string, string> = {
    excellent: 'ممتاز',
    very_good: 'جيد جداً',
    good: 'جيد',
    needs_work: 'يحتاج إلى تحسين',
};
</script>

<template>
    <Head title="لوحة التحكم" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-content-strong">لوحة التحكم</h2>
        </template>

        <div class="mx-auto max-w-7xl space-y-6">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6">
                <div class="rounded-lg bg-surface p-4 shadow-sm">
                    <p class="text-sm text-content-muted">المساجد</p>
                    <p class="mt-1 text-2xl font-bold text-content">{{ stats.mosques_count }}</p>
                </div>
                <div class="rounded-lg bg-surface p-4 shadow-sm">
                    <p class="text-sm text-content-muted">الحلقات</p>
                    <p class="mt-1 text-2xl font-bold text-content">{{ stats.circles_count }}</p>
                </div>
                <div class="rounded-lg bg-surface p-4 shadow-sm">
                    <p class="text-sm text-content-muted">الطلاب</p>
                    <p class="mt-1 text-2xl font-bold text-content">{{ stats.students_count }}</p>
                </div>
                <div class="rounded-lg bg-surface p-4 shadow-sm">
                    <p class="text-sm text-content-muted">المعلمون</p>
                    <p class="mt-1 text-2xl font-bold text-content">{{ stats.teachers_count }}</p>
                </div>
                <div class="rounded-lg bg-surface p-4 shadow-sm">
                    <p class="text-sm text-content-muted">تسميعات اليوم</p>
                    <p class="mt-1 text-2xl font-bold text-teal-700 dark:text-teal-300">{{ stats.recitations_today }}</p>
                </div>
                <div class="rounded-lg bg-surface p-4 shadow-sm">
                    <p class="text-sm text-content-muted">تسميعات هذا الشهر</p>
                    <p class="mt-1 text-2xl font-bold text-teal-700 dark:text-teal-300">{{ stats.recitations_this_month }}</p>
                </div>
            </div>

            <div class="rounded-lg bg-surface shadow-sm">
                <div class="border-b border-edge px-4 py-3">
                    <h3 class="text-base font-semibold text-content">آخر التسميعات</h3>
                </div>
                <div v-if="recentRecitations.length === 0" class="p-4">
                    <EmptyState title="لا توجد تسميعات حديثة" />
                </div>
                <ul v-else class="divide-y divide-edge-soft">
                    <li
                        v-for="recitation in recentRecitations"
                        :key="recitation.id"
                        class="flex items-center justify-between px-4 py-3 text-sm"
                    >
                        <div>
                            <p class="font-medium text-content-strong">
                                {{ recitation.student?.user?.name ?? 'طالب' }}
                            </p>
                            <p class="text-content-muted">
                                سورة {{ recitation.surah_number }} — الآيات {{ recitation.ayah_from }} إلى {{ recitation.ayah_to }}
                            </p>
                        </div>
                        <StatusBadge v-if="recitation.rating" :status="recitation.rating" :labels="ratingLabels" />
                    </li>
                </ul>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
