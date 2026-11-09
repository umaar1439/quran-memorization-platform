<script setup lang="ts">
import { computed } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import DataTable, { type DataTableColumn } from '@/Components/DataTable.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import type { ExamResultPageModel, StudentExamsPageProps } from '@/Types/api/exam';

const props = defineProps<StudentExamsPageProps>();

const statusLabels: Record<string, string> = {
    draft: 'مسودة',
    submitted: 'مُقدَّم',
    approved: 'معتمد',
};

interface ResultRow {
    id: number;
    exam_title: string;
    score: number | null;
    grade: string;
    status: string;
}

const columns: DataTableColumn[] = [
    { key: 'exam_title', label: 'الامتحان' },
    { key: 'score', label: 'الدرجة' },
    { key: 'grade', label: 'التقدير' },
    { key: 'status', label: 'الحالة' },
];

// تجهيز الصفوف للعرض فقط (اختيار الأعمدة المعروضة) — لا منطق عمل ولا شبكة.
const rows = computed(() =>
    props.results.data.map((result: ExamResultPageModel) => ({
        id: result.id,
        exam_title: result.exam?.title ?? '—',
        score: result.score,
        grade: result.grade ?? '—',
        status: result.status,
    })),
);

function onPageChange(page: number): void {
    router.get(route('student.exams'), { page }, { preserveState: true });
}
</script>

<template>
    <Head title="امتحاناتي" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-lg font-semibold leading-tight text-content-strong sm:text-xl">نتائج الامتحانات</h2>
        </template>

        <div class="mx-auto max-w-4xl">
            <DataTable
                :columns="columns"
                :rows="rows"
                :current-page="results.current_page"
                :last-page="results.last_page"
                :total="results.total"
                :searchable="false"
                @update:page="onPageChange"
            >
                <template #cell-status="{ row }">
                    <StatusBadge :status="(row as unknown as ResultRow).status" :labels="statusLabels" />
                </template>
            </DataTable>
        </div>
    </AuthenticatedLayout>
</template>
