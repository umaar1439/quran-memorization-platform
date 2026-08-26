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
                    <Link :href="route('admin.circles.index')" class="text-sm font-medium text-teal-600 hover:text-teal-700">
                        → رجوع إلى الحلقات
                    </Link>
                    <h2 class="mt-1 text-xl font-semibold leading-tight text-gray-800">{{ circle.name }}</h2>
                    <p class="mt-1 text-sm text-gray-500">
                        {{ circle.mosque?.name ?? '—' }} · {{ circle.level ?? 'بدون مستوى' }}
                    </p>
                </div>
                <StatusBadge :status="circle.status" />
            </div>
        </template>

        <div class="mx-auto max-w-7xl space-y-6">
            <!-- ملخص عام -->
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div class="rounded-lg bg-white p-5 shadow-sm">
                    <p class="text-sm text-gray-500">إجمالي الطلاب</p>
                    <p class="mt-1 text-2xl font-bold text-gray-900">{{ summary.total_students }}</p>
                </div>
                <div class="rounded-lg bg-white p-5 shadow-sm">
                    <p class="text-sm text-gray-500">تسميعات هذا الشهر</p>
                    <p class="mt-1 text-2xl font-bold text-teal-700">{{ summary.recitations_this_month }}</p>
                </div>
                <div class="rounded-lg bg-white p-5 shadow-sm">
                    <p class="text-sm text-gray-500">نسبة الحضور</p>
                    <p class="mt-1 text-2xl font-bold text-gray-900">{{ summary.attendance_rate }}%</p>
                </div>
                <div class="rounded-lg bg-white p-5 shadow-sm">
                    <p class="text-sm text-gray-500">التقدم الإجمالي</p>
                    <p class="mt-1 text-2xl font-bold text-gray-900">{{ summary.overall_progress }}%</p>
                    <ProgressBar class="mt-2" :value="summary.overall_progress" :show-percentage="false" color="teal" />
                </div>
            </div>

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                <!-- بيانات المعلم -->
                <div class="rounded-lg bg-white p-5 shadow-sm">
                    <h3 class="mb-3 text-base font-semibold text-gray-900">المعلم المشرف</h3>
                    <div v-if="circle.teacher" class="space-y-2 text-sm">
                        <p class="font-medium text-gray-800">{{ circle.teacher.name }}</p>
                        <p class="text-gray-500">{{ circle.teacher.email ?? '—' }}</p>
                        <p class="text-gray-500">{{ circle.teacher.phone ?? '—' }}</p>
                        <StatusBadge v-if="circle.teacher.status" :status="circle.teacher.status" />
                    </div>
                    <EmptyState v-else title="لا يوجد معلم معيّن" />
                </div>

                <!-- الطالب المتميز -->
                <div class="rounded-lg bg-white p-5 shadow-sm lg:col-span-2">
                    <h3 class="mb-3 text-base font-semibold text-gray-900">الطالب المتميز</h3>
                    <div v-if="topStudent" class="flex flex-wrap items-center gap-4">
                        <span class="flex h-14 w-14 items-center justify-center rounded-full bg-teal-100 text-2xl">🏆</span>
                        <div>
                            <p class="text-lg font-semibold text-gray-900">{{ topStudent.student.user?.name ?? 'طالب' }}</p>
                            <p class="text-sm text-gray-500">
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
            <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
                <div class="border-b border-gray-200 px-4 py-3">
                    <h3 class="text-base font-semibold text-gray-900">الطلاب المسجلون</h3>
                </div>
                <div v-if="students.length === 0" class="p-4">
                    <EmptyState title="لا يوجد طلاب في هذه الحلقة" />
                </div>
                <div v-else class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-right">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-xs font-semibold uppercase text-gray-500">الطالب</th>
                                <th class="px-4 py-3 text-xs font-semibold uppercase text-gray-500">الحالة</th>
                                <th class="px-4 py-3 text-xs font-semibold uppercase text-gray-500">آخر نشاط</th>
                                <th class="px-4 py-3 text-xs font-semibold uppercase text-gray-500">تسميعات</th>
                                <th class="px-4 py-3 text-xs font-semibold uppercase text-gray-500">آيات محفوظة</th>
                                <th class="px-4 py-3 text-xs font-semibold uppercase text-gray-500">الدقة</th>
                                <th class="px-4 py-3 text-xs font-semibold uppercase text-gray-500">إجراءات</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="item in students" :key="item.student.id" class="hover:bg-gray-50">
                                <td class="px-4 py-3 text-sm font-medium text-gray-800">{{ item.student.user?.name ?? 'طالب' }}</td>
                                <td class="px-4 py-3 text-sm"><StatusBadge :status="item.student.status" /></td>
                                <td class="px-4 py-3 text-sm text-gray-600">{{ formatDate(item.last_activity_at) }}</td>
                                <td class="px-4 py-3 text-sm text-gray-600">{{ item.recitation_count }}</td>
                                <td class="px-4 py-3 text-sm text-gray-600">{{ item.memorized_ayahs }}</td>
                                <td class="px-4 py-3 text-sm text-gray-600">{{ item.accuracy }}%</td>
                                <td class="px-4 py-3 text-sm">
                                    <Link :href="route('students.show', item.student.id)" class="font-medium text-teal-600 hover:underline">
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
