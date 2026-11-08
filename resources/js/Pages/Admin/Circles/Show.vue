<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import EmptyState from '@/Components/EmptyState.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import ProgressBar from '@/Components/ProgressBar.vue';
import type { Circle, StudentProfile } from '@/Types';

interface StudentStat {
    student: StudentProfile;
    memorized_ayahs: number;
    recitation_count: number;
    accuracy: number;
    last_activity_at: string | null;
    progress_percentage: number;
}

interface Summary {
    total_students: number;
    recitations_this_month: number;
    attendance_rate: number;
    overall_progress: number;
}

defineProps<{
    circle: Circle;
    summary: Summary;
    students: StudentStat[];
    topStudent: StudentStat | null;
}>();

function formatDate(value: string | null): string {
    if (!value) return '—';

    return new Date(value).toLocaleDateString('ar-EG', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
}
</script>

<template>
    <Head :title="`تفاصيل حلقة ${circle.name}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-start justify-between">
                <div>
                    <Link :href="route('admin.circles.index')" class="text-sm font-medium text-teal-600 dark:text-teal-400 hover:text-teal-700 dark:hover:text-teal-300">
                        → رجوع إلى الحلقات
                    </Link>
                    <h2 class="mt-1 text-xl font-semibold leading-tight text-content-strong">{{ circle.name }}</h2>
                    <p class="mt-1 text-sm text-content-muted">
                        {{ circle.mosque?.name ?? '—' }} · {{ circle.level ?? 'بدون مستوى' }}
                    </p>
                </div>
                <StatusBadge :status="circle.status" />
            </div>
        </template>

        <div class="mx-auto max-w-7xl space-y-6">
            <!-- ملخص عام -->
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div class="rounded-lg bg-surface p-5 shadow-sm">
                    <p class="text-sm text-content-muted">إجمالي الطلاب</p>
                    <p class="mt-1 text-2xl font-bold text-content">{{ summary.total_students }}</p>
                </div>
                <div class="rounded-lg bg-surface p-5 shadow-sm">
                    <p class="text-sm text-content-muted">تسميعات هذا الشهر</p>
                    <p class="mt-1 text-2xl font-bold text-teal-700 dark:text-teal-300">{{ summary.recitations_this_month }}</p>
                </div>
                <div class="rounded-lg bg-surface p-5 shadow-sm">
                    <p class="text-sm text-content-muted">نسبة الحضور</p>
                    <p class="mt-1 text-2xl font-bold text-content">{{ summary.attendance_rate }}%</p>
                </div>
                <div class="rounded-lg bg-surface p-5 shadow-sm">
                    <p class="text-sm text-content-muted">التقدم الإجمالي</p>
                    <p class="mt-1 text-2xl font-bold text-content">{{ summary.overall_progress }}%</p>
                    <ProgressBar class="mt-2" :value="summary.overall_progress" :show-percentage="false" color="teal" />
                </div>
            </div>

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                <!-- بيانات المعلم -->
                <div class="rounded-lg bg-surface p-5 shadow-sm">
                    <h3 class="mb-3 text-base font-semibold text-content">المعلم المشرف</h3>
                    <div v-if="circle.teacher" class="space-y-2 text-sm">
                        <p class="font-medium text-content-strong">{{ circle.teacher.name }}</p>
                        <p class="text-content-muted">{{ circle.teacher.email ?? '—' }}</p>
                        <p class="text-content-muted">{{ circle.teacher.phone ?? '—' }}</p>
                        <StatusBadge v-if="circle.teacher.status" :status="circle.teacher.status" />
                    </div>
                    <EmptyState v-else title="لا يوجد معلم معيّن" />
                </div>

                <!-- الطالب المتميز -->
                <div class="rounded-lg bg-surface p-5 shadow-sm lg:col-span-2">
                    <h3 class="mb-3 text-base font-semibold text-content">الطالب المتميز</h3>
                    <div v-if="topStudent" class="flex flex-wrap items-center gap-4">
                        <span class="flex h-14 w-14 items-center justify-center rounded-full bg-teal-100 dark:bg-teal-500/15 text-2xl">🏆</span>
                        <div>
                            <p class="text-lg font-semibold text-content">{{ topStudent.student.user?.name ?? 'طالب' }}</p>
                            <p class="text-sm text-content-muted">
                                {{ topStudent.memorized_ayahs }} آية محفوظة · {{ topStudent.recitation_count }} تسميع · دقة {{ topStudent.accuracy }}%
                            </p>
                        </div>
                        <div class="ms-auto w-full sm:w-64">
                            <ProgressBar :value="topStudent.progress_percentage" label="تقدم الحفظ" color="teal" />
                        </div>
                    </div>
                    <EmptyState v-else title="لا يوجد طلاب بعد" />
                </div>
            </div>

            <!-- قائمة الطلاب -->
            <div class="overflow-hidden rounded-lg border border-edge bg-surface shadow-sm">
                <div class="border-b border-edge px-4 py-3">
                    <h3 class="text-base font-semibold text-content">الطلاب المسجلون</h3>
                </div>
                <div v-if="students.length === 0" class="p-4">
                    <EmptyState title="لا يوجد طلاب في هذه الحلقة" />
                </div>
                <div v-else class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-edge text-right">
                        <thead class="bg-surface-muted">
                            <tr>
                                <th class="px-4 py-3 text-xs font-semibold uppercase text-content-muted">الطالب</th>
                                <th class="px-4 py-3 text-xs font-semibold uppercase text-content-muted">الحالة</th>
                                <th class="px-4 py-3 text-xs font-semibold uppercase text-content-muted">آخر نشاط</th>
                                <th class="px-4 py-3 text-xs font-semibold uppercase text-content-muted">تسميعات</th>
                                <th class="px-4 py-3 text-xs font-semibold uppercase text-content-muted">آيات محفوظة</th>
                                <th class="px-4 py-3 text-xs font-semibold uppercase text-content-muted">الدقة</th>
                                <th class="px-4 py-3 text-xs font-semibold uppercase text-content-muted">إجراءات</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-edge-soft">
                            <tr v-for="item in students" :key="item.student.id" class="hover:bg-surface-muted">
                                <td class="px-4 py-3 text-sm font-medium text-content-strong">{{ item.student.user?.name ?? 'طالب' }}</td>
                                <td class="px-4 py-3 text-sm"><StatusBadge :status="item.student.status" /></td>
                                <td class="px-4 py-3 text-sm text-content-soft">{{ formatDate(item.last_activity_at) }}</td>
                                <td class="px-4 py-3 text-sm text-content-soft">{{ item.recitation_count }}</td>
                                <td class="px-4 py-3 text-sm text-content-soft">{{ item.memorized_ayahs }}</td>
                                <td class="px-4 py-3 text-sm text-content-soft">{{ item.accuracy }}%</td>
                                <td class="px-4 py-3 text-sm">
                                    <Link :href="route('students.show', item.student.id)" class="font-medium text-teal-600 dark:text-teal-400 hover:underline">
                                        عرض ملف الطالب
                                    </Link>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
