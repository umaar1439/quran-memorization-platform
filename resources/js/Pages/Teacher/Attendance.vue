<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import DataTable, { type DataTableColumn } from '@/Components/DataTable.vue';
import { TeacherActivityApi } from '@/Services/TeacherActivityApi';
import type { TeacherActivityIndexPageProps, TeacherActivityPageModel } from '@/Types/api/teacherActivity';

/**
 * صفحة عرض فقط (قراءة بحتة): لا نموذج ولا فلاتر، والترقيم يمر عبر
 * TeacherActivityApi. الحضور الفعلي للطلاب في صفحة «الحضور».
 */
defineProps<TeacherActivityIndexPageProps>();

const columns: DataTableColumn[] = [
    { key: 'date', label: 'التاريخ' },
    { key: 'first_activity_at', label: 'أول نشاط' },
    { key: 'last_activity_at', label: 'آخر نشاط' },
    { key: 'actions_count', label: 'عدد الأنشطة' },
];

/** cast 'date'/'datetime' يُسلسَل ISO 8601 كاملاً؛ نعرض التاريخ والوقت فقط. */
function shortDate(value: string | null): string {
    return value ? value.slice(0, 10) : '—';
}

function shortTime(value: string | null): string {
    return value ? value.slice(11, 16) : '—';
}

function onPageChange(page: number): void {
    TeacherActivityApi.visit(page);
}
</script>

<template>
    <Head title="سجل نشاطي" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-lg font-semibold leading-tight text-content-strong sm:text-xl">سجل نشاطي</h2>
        </template>

        <div class="mx-auto max-w-4xl">
            <p class="mb-4 text-sm text-content-muted">
                يُحسب هذا السجل تلقائياً بناءً على نشاطك الفعلي في النظام (تسجيل تسميعات، حضور، إلخ).
                لحضور الطلاب استخدم صفحة «الحضور».
            </p>

            <DataTable
                :columns="columns"
                :rows="attendances.data"
                :current-page="attendances.current_page"
                :last-page="attendances.last_page"
                :total="attendances.total"
                :searchable="false"
                @update:page="onPageChange"
            >
                <template #cell-date="{ row }">
                    {{ shortDate((row as TeacherActivityPageModel).date) }}
                </template>
                <template #cell-first_activity_at="{ row }">
                    {{ shortTime((row as TeacherActivityPageModel).first_activity_at) }}
                </template>
                <template #cell-last_activity_at="{ row }">
                    {{ shortTime((row as TeacherActivityPageModel).last_activity_at) }}
                </template>
            </DataTable>
        </div>
    </AuthenticatedLayout>
</template>
