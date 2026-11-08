<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import StatusBadge from '@/Components/StatusBadge.vue';
import EmptyState from '@/Components/EmptyState.vue';
import StatCard from '@/Components/StatCard.vue';
import SectionCard from '@/Components/SectionCard.vue';
import PageHeader from '@/Components/PageHeader.vue';
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
            <PageHeader
                title="لوحة التحكم"
                description="نظرة سريعة على المساجد والحلقات والنشاط اليومي."
            />
        </template>

        <div class="space-y-6">
            <!--
             * عمودان على الجوال (بطاقات مدمجة) ثم 3 ثم 6، حتى تظهر الأرقام
             * المهمة دون تمرير طويل.
             -->
            <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 sm:gap-4 xl:grid-cols-6">
                <StatCard label="المساجد" :value="stats.mosques_count" icon="mosque" />
                <StatCard label="الحلقات" :value="stats.circles_count" icon="circles" />
                <StatCard label="الطلاب" :value="stats.students_count" icon="graduation-cap" />
                <StatCard label="المعلمون" :value="stats.teachers_count" icon="user-check" />
                <StatCard label="تسميعات اليوم" :value="stats.recitations_today" icon="book" accent="teal" />
                <StatCard label="تسميعات هذا الشهر" :value="stats.recitations_this_month" icon="chart" accent="teal" />
            </div>

            <SectionCard title="آخر التسميعات" :padded="false">
                <EmptyState
                    v-if="recentRecitations.length === 0"
                    title="لا توجد تسميعات حديثة"
                    description="ستظهر هنا آخر التسميعات المسجَّلة في حلقاتك."
                    icon="book"
                />

                <ul v-else class="divide-y divide-edge-soft">
                    <li
                        v-for="recitation in recentRecitations"
                        :key="recitation.id"
                        class="flex flex-wrap items-center justify-between gap-x-3 gap-y-1 px-4 py-3 text-sm sm:px-5"
                    >
                        <div class="min-w-0">
                            <p class="truncate font-medium text-content-strong">
                                {{ recitation.student?.user?.name ?? 'طالب' }}
                            </p>
                            <p class="text-content-muted">
                                سورة {{ recitation.surah_number }} — الآيات {{ recitation.ayah_from }} إلى {{ recitation.ayah_to }}
                            </p>
                        </div>
                        <StatusBadge v-if="recitation.rating" :status="recitation.rating" :labels="ratingLabels" />
                    </li>
                </ul>
            </SectionCard>
        </div>
    </AuthenticatedLayout>
</template>
