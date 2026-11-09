import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { AttendanceApi } from '@/Services/AttendanceApi';
import type { SelectOption } from '@/Types/api/common';
import type {
    AttendanceFilterKey,
    AttendanceFilters,
    AttendanceIndexPageProps,
} from '@/Types/api/attendance';
import type { PageProps } from '@/Types';

/** يطابق App\Enums\AttendanceStatus — نصوص واجهة فقط. */
const STATUS_OPTIONS: SelectOption[] = [
    { value: 'present', label: 'حاضر' },
    { value: 'absent', label: 'غائب' },
    { value: 'late', label: 'متأخر' },
    { value: 'excused', label: 'مستأذن' },
];

const statusLabels: Record<string, string> = Object.fromEntries(
    STATUS_OPTIONS.map((option) => [option.value, option.label]),
);

/**
 * ViewModel لسجل الحضور المفصّل في صفحة الإدارة: الفلاتر والترقيم فقط —
 * البيانات تأتي جاهزة في props (StudentAttendanceQuery::history) والصفحة تعرض.
 */
export function useAttendanceHistory(props: AttendanceIndexPageProps) {
    const canManage = computed(() =>
        usePage<PageProps>().props.auth.permissions.includes('attendance.manage'),
    );

    const circleOptions = computed<SelectOption[]>(() =>
        props.sheet.circles.map((circle) => ({
            value: circle.id,
            label: circle.mosque_name ? `${circle.name} (${circle.mosque_name})` : circle.name,
        })),
    );

    /** حقول شريط الفلاتر — الصفحة تعرضها بـ v-for بلا منطق. */
    const filterFields = computed<
        { key: AttendanceFilterKey; label: string; allLabel: string; options: SelectOption[] }[]
    >(() => [
        { key: 'circle_id', label: 'الحلقة', allLabel: 'كل الحلقات', options: circleOptions.value },
        { key: 'status', label: 'الحالة', allLabel: 'كل الحالات', options: STATUS_OPTIONS },
    ]);

    /** يبني استعلام القائمة الحالي مع تجاوزات، ويحذف القيم الفارغة. */
    function query(overrides: Partial<AttendanceFilters> & { page?: number }): AttendanceFilters {
        const merged: AttendanceFilters = { ...props.filters, ...overrides };

        (Object.keys(merged) as (keyof AttendanceFilters)[]).forEach((key) => {
            const value = merged[key];

            if (value === undefined || value === null || value === '') {
                delete merged[key];
            }
        });

        return merged;
    }

    function visit(overrides: Partial<AttendanceFilters> & { page?: number }): void {
        AttendanceApi.visitSheet(query(overrides), 'admin.attendance.index');
    }

    function onFilterChange(key: AttendanceFilterKey, event: Event): void {
        const value = (event.target as HTMLSelectElement).value;
        const overrides: Partial<AttendanceFilters> & { page?: number } = { page: undefined };

        overrides[key] = value === '' ? undefined : value;

        visit(overrides);
    }

    function onDateFilterChange(event: Event): void {
        const date = (event.target as HTMLInputElement).value;

        visit({ date: date === '' ? undefined : date, page: undefined });
    }

    function onPageChange(page: number): void {
        visit({ page });
    }

    function clearFilters(): void {
        AttendanceApi.visitSheet({}, 'admin.attendance.index');
    }

    return {
        filters: props.filters,
        filterFields,
        statusLabels,
        canManage,
        onFilterChange,
        onDateFilterChange,
        onPageChange,
        clearFilters,
    };
}
