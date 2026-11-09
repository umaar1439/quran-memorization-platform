import { computed } from 'vue';
import { StudentGoalApi } from '@/Services/StudentGoalApi';
import type { StudentGoalPageModel, StudentGoalsPageProps } from '@/Types/api/goal';

const TARGET_TYPE_LABELS: Record<string, string> = {
    memorization: 'حفظ',
    revision: 'مراجعة',
};

const PERIOD_TYPE_LABELS: Record<string, string> = {
    daily: 'يومي',
    weekly: 'أسبوعي',
    monthly: 'شهري',
};

const STATUS_LABELS: Record<string, string> = {
    active: 'نشط',
    completed: 'مكتمل',
    failed: 'فشل',
    cancelled: 'ملغي',
};

/** صف جاهز للعرض: تسميات عربية ونسبة إنجاز كنص. */
export type StudentGoalRow = {
    id: number;
    surah: string;
    target_type: string;
    period_type: string;
    target_amount: number;
    achieved_amount: number;
    completion_percentage: string;
    status: string;
};

/**
 * ViewModel لصفحة أهداف الطالب: تحويل الصفوف إلى عرض مقروء والتنقّل بين
 * الصفحات — والمكوّن يعرض فقط.
 */
export function useStudentGoals(props: StudentGoalsPageProps) {
    const rows = computed<StudentGoalRow[]>(() =>
        props.goals.data.map((goal: StudentGoalPageModel) => ({
            id: goal.id,
            surah: goal.surah?.name_ar ?? (goal.juz_number ? `الجزء ${goal.juz_number}` : '—'),
            target_type: TARGET_TYPE_LABELS[goal.target_type] ?? goal.target_type,
            period_type: PERIOD_TYPE_LABELS[goal.period_type] ?? goal.period_type,
            target_amount: goal.target_amount,
            achieved_amount: goal.achieved_amount,
            completion_percentage: `${goal.completion_percentage}%`,
            status: goal.status,
        })),
    );

    function onPageChange(page: number): void {
        StudentGoalApi.visit(page);
    }

    return {
        rows,
        statusLabels: STATUS_LABELS,
        onPageChange,
    };
}
