<script setup lang="ts">
import { computed } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import EmptyState from '@/Components/EmptyState.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import ProgressBar from '@/Components/ProgressBar.vue';
import DataTable, { type DataTableColumn } from '@/Components/DataTable.vue';
import StatCard from '@/Components/StatCard.vue';
import PageHeader from '@/Components/PageHeader.vue';
import AppIcon from '@/Components/AppIcon.vue';
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

const props = defineProps<{
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

const studentColumns: DataTableColumn[] = [
    { key: 'name', label: 'الطالب' },
    { key: 'status', label: 'الحالة' },
    { key: 'last_activity_at', label: 'آخر نشاط' },
    { key: 'recitation_count', label: 'تسميعات' },
    { key: 'memorized_ayahs', label: 'آيات محفوظة' },
    { key: 'accuracy', label: 'الدقة' },
    { key: 'actions', label: 'إجراءات' },
];

interface StudentRow {
    id: number;
    name: string;
    status: string;
    last_activity_at: string;
    recitation_count: number;
    memorized_ayahs: number;
    accuracy: string;
}

/* تحويل السجلات إلى صفوف مسطّحة ليعرضها DataTable (جدول على الحاسب، بطاقات على الجوال) */
const studentRows = computed<StudentRow[]>(() =>
    props.students.map((item) => ({
        id: item.student.id,
        name: item.student.user?.name ?? 'طالب',
        status: item.student.status,
        last_activity_at: formatDate(item.last_activity_at),
        recitation_count: item.recitation_count,
        memorized_ayahs: item.memorized_ayahs,
        accuracy: `${item.accuracy}%`,
    })),
);
</script>

<template>
    <Head :title="`تفاصيل حلقة ${circle.name}`" />

    <AuthenticatedLayout>
        <template #header>
            <PageHeader
                :title="circle.name"
                :description="`${circle.mosque?.name ?? '—'} · ${circle.level ?? 'بدون مستوى'}`"
                :back-href="route('admin.circles.index')"
                back-label="رجوع إلى الحلقات"
            >
                <template #meta>
                    <StatusBadge :status="circle.status" />
                </template>
            </PageHeader>
        </template>

        <div class="space-y-6">
            <div class="grid grid-cols-2 gap-3 sm:grid-cols-2 sm:gap-4 lg:grid-cols-4">
                <StatCard label="إجمالي الطلاب" :value="summary.total_students" icon="graduation-cap" />
                <StatCard label="تسميعات هذا الشهر" :value="summary.recitations_this_month" icon="book" accent="teal" />
                <StatCard label="نسبة الحضور" :value="`${summary.attendance_rate}%`" icon="check-circle" />
                <StatCard label="التقدم الإجمالي" :value="`${summary.overall_progress}%`" icon="chart" accent="teal" />
            </div>

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                <!-- بيانات المعلم -->
                <div class="card p-5">
                    <h3 class="mb-3 text-base font-semibold text-content">المعلم المشرف</h3>
                    <div v-if="circle.teacher" class="space-y-2 text-sm">
                        <p class="font-medium text-content-strong">{{ circle.teacher.name }}</p>
                        <p class="break-words text-content-muted">{{ circle.teacher.email ?? '—' }}</p>
                        <p class="text-content-muted">{{ circle.teacher.phone ?? '—' }}</p>
                        <StatusBadge v-if="circle.teacher.status" :status="circle.teacher.status" />
                    </div>
                    <EmptyState v-else title="لا يوجد معلم معيّن" icon="user-check" />
                </div>

                <!-- الطالب المتميز -->
                <div class="card p-5 lg:col-span-2">
                    <h3 class="mb-3 text-base font-semibold text-content">الطالب المتميز</h3>
                    <div v-if="topStudent" class="flex flex-wrap items-center gap-4">
                        <span
                            class="flex h-12 w-12 items-center justify-center rounded-full bg-teal-50 text-teal-700 dark:bg-teal-500/10 dark:text-teal-300"
                        >
                            <AppIcon name="award" size="lg" />
                        </span>
                        <div class="min-w-0">
                            <p class="truncate text-lg font-semibold text-content-strong">
                                {{ topStudent.student.user?.name ?? 'طالب' }}
                            </p>
                            <p class="text-sm text-content-muted">
                                {{ topStudent.memorized_ayahs }} آية محفوظة · {{ topStudent.recitation_count }} تسميع · دقة {{ topStudent.accuracy }}%
                            </p>
                        </div>
                        <div class="w-full sm:ms-auto sm:w-64">
                            <ProgressBar :value="topStudent.progress_percentage" label="تقدم الحفظ" color="teal" />
                        </div>
                    </div>
                    <EmptyState v-else title="لا يوجد طلاب بعد" icon="users" />
                </div>
            </div>

            <!-- قائمة الطلاب: جدول على الحاسب وبطاقات على الجوال -->
            <section class="space-y-3">
                <h3 class="text-base font-semibold text-content">الطلاب المسجلون</h3>
                <DataTable
                    :columns="studentColumns"
                    :rows="studentRows"
                    :searchable="false"
                    label="قائمة الطلاب المسجلين في الحلقة"
                >
                    <template #cell-status="{ row }">
                        <StatusBadge :status="(row as StudentRow).status" />
                    </template>
                    <template #cell-actions="{ row }">
                        <Link
                            :href="route('students.show', (row as StudentRow).id)"
                            class="link"
                        >
                            عرض ملف الطالب
                        </Link>
                    </template>
                </DataTable>
            </section>
        </div>
    </AuthenticatedLayout>
</template>
