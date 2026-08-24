<script setup lang="ts">
import { computed } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import EmptyState from '@/Components/EmptyState.vue';
import ProgressBar from '@/Components/ProgressBar.vue';
import ProgressRing from '@/Components/ProgressRing.vue';
import type { StudentAnalytics, StudentProfile } from '@/Types';

const props = defineProps<{
    analytics: StudentAnalytics;
    student: StudentProfile;
}>();

const errorTypeLabels: Record<string, string> = {
    memorization: 'الحفظ',
    tajweed: 'التجويد',
    pronunciation: 'النطق',
    tashkeel: 'التشكيل',
};

const coveredSurahs = computed(() =>
    props.analytics.progress_by_surah.filter((s) => s.ayahs_covered > 0),
);

const coveredJuz = computed(() =>
    props.analytics.progress_by_juz.filter((j) => j.recitations_count > 0),
);
</script>

<template>
    <Head title="لوحتي" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">لوحة الطالب</h2>
        </template>

        <div class="mx-auto max-w-7xl space-y-6">
            <!-- نظرة عامة -->
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div class="flex flex-col items-center justify-center rounded-lg bg-white p-6 shadow-sm">
                    <ProgressRing :percentage="analytics.overall_progress.percentage" label="التقدم الكلي في الحفظ" />
                    <p class="mt-3 text-sm text-gray-500">
                        {{ analytics.overall_progress.memorized_ayahs }} من
                        {{ analytics.overall_progress.total_ayahs }} آية
                    </p>
                </div>

                <div class="rounded-lg bg-white p-6 shadow-sm">
                    <p class="text-sm text-gray-500">نسبة الحضور</p>
                    <p class="mt-2 text-2xl font-bold text-teal-700">{{ analytics.attendance_rate.percentage }}%</p>
                    <ProgressBar class="mt-3" :value="analytics.attendance_rate.percentage" :show-percentage="false" color="teal" />
                    <p class="mt-2 text-xs text-gray-500">
                        {{ analytics.attendance_rate.attendance_days }} يوم حضور من
                        {{ analytics.attendance_rate.study_days }} يوم دراسة
                    </p>
                </div>

                <div class="rounded-lg bg-white p-6 shadow-sm">
                    <p class="text-sm text-gray-500">سلسلة الأيام المتتالية</p>
                    <p class="mt-2 text-2xl font-bold text-amber-600">{{ analytics.streak }}</p>
                    <p class="mt-2 text-xs text-gray-500">يوم متتالٍ من النشاط</p>
                </div>

                <div class="rounded-lg bg-white p-6 shadow-sm">
                    <p class="text-sm text-gray-500">التسجيل</p>
                    <dl class="mt-2 space-y-1 text-sm">
                        <div class="flex justify-between gap-2">
                            <dt class="text-gray-500">المسجد</dt>
                            <dd class="font-medium text-gray-800">{{ student.mosque?.name ?? '—' }}</dd>
                        </div>
                        <div class="flex justify-between gap-2">
                            <dt class="text-gray-500">الحلقة</dt>
                            <dd class="font-medium text-gray-800">{{ student.circle?.name ?? '—' }}</dd>
                        </div>
                        <div class="flex justify-between gap-2">
                            <dt class="text-gray-500">رقم القيد</dt>
                            <dd class="font-medium text-gray-800">{{ student.enrollment_number ?? '—' }}</dd>
                        </div>
                    </dl>
                </div>
            </div>

            <!-- التقدم بالسور -->
            <div class="rounded-lg bg-white shadow-sm">
                <div class="border-b border-gray-200 px-4 py-3">
                    <h3 class="text-base font-semibold text-gray-900">التقدم بالسور</h3>
                </div>
                <EmptyState v-if="coveredSurahs.length === 0" title="لا يوجد تقدّم محفوظ بعد" />
                <ul v-else class="divide-y divide-gray-100">
                    <li v-for="surah in coveredSurahs" :key="surah.surah_number" class="px-4 py-3">
                        <div class="flex items-center justify-between text-sm">
                            <span class="font-medium text-gray-800">
                                {{ surah.surah_name ?? `سورة ${surah.surah_number}` }}
                            </span>
                            <span class="text-gray-500">
                                {{ surah.ayahs_covered }} / {{ surah.total_ayahs }} آية
                            </span>
                        </div>
                        <ProgressBar class="mt-2" :value="surah.percentage" :show-percentage="true" color="blue" />
                    </li>
                </ul>
            </div>
            <!-- الأجزاء المغطاة -->
            <div v-if="coveredJuz.length > 0" class="rounded-lg bg-white shadow-sm">
                <div class="border-b border-gray-200 px-4 py-3">
                    <h3 class="text-base font-semibold text-gray-900">الأجزاء</h3>
                </div>
                <div class="flex flex-wrap gap-2 p-4">
                    <span v-for="juz in coveredJuz" :key="juz.juz_number" class="inline-flex items-center rounded-full bg-teal-50 px-3 py-1 text-sm font-medium text-teal-700">
                        الجزء {{ juz.juz_number }}
                    </span>
                </div>
            </div>

            <!-- نقاط القوة والضعف -->
            <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                <div class="rounded-lg bg-white shadow-sm">
                    <div class="border-b border-gray-200 px-4 py-3">
                        <h3 class="text-base font-semibold text-gray-900">نقاط القوة</h3>
                    </div>
                    <EmptyState v-if="analytics.strengths.length === 0" title="لا توجد بيانات كافية بعد" />
                    <ul v-else class="divide-y divide-gray-100">
                        <li v-for="strength in analytics.strengths" :key="strength.surah_number" class="flex items-center justify-between px-4 py-3 text-sm">
                            <span class="font-medium text-gray-800">سورة {{ strength.surah_number }}</span>
                            <span class="text-green-700">{{ strength.excellent_ratio }}% ممتاز</span>
                        </li>
                    </ul>
                </div>

                <div class="rounded-lg bg-white shadow-sm">
                    <div class="border-b border-gray-200 px-4 py-3">
                        <h3 class="text-base font-semibold text-gray-900">نقاط تحتاج تحسيناً</h3>
                    </div>
                    <EmptyState v-if="analytics.weaknesses.length === 0" title="لا توجد بيانات كافية بعد" />
                    <ul v-else class="divide-y divide-gray-100">
                        <li v-for="weakness in analytics.weaknesses" :key="weakness.surah_number" class="flex items-center justify-between px-4 py-3 text-sm">
                            <span class="font-medium text-gray-800">سورة {{ weakness.surah_number }}</span>
                            <span class="text-red-600">{{ weakness.needs_work_ratio }}% يحتاج عمل</span>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- توزيع الأخطاء -->
            <div class="rounded-lg bg-white shadow-sm">
                <div class="border-b border-gray-200 px-4 py-3">
                    <h3 class="text-base font-semibold text-gray-900">توزيع الأخطاء</h3>
                </div>
                <div class="space-y-4 p-4">
                    <div v-for="(value, type) in analytics.error_distribution" :key="type">
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-gray-600">{{ errorTypeLabels[type] ?? type }}</span>
                            <span class="font-medium text-gray-800">{{ value.count }} ({{ value.percentage }}%)</span>
                        </div>
                        <ProgressBar class="mt-2" :value="value.percentage" :show-percentage="false" color="amber" />
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
