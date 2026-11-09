import { computed, reactive, ref, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { useToast } from '@/Composables/useToast';
import { AttendanceApi } from '@/Services/AttendanceApi';
import type { FormErrors, SelectOption } from '@/Types/api/common';
import type {
    AttendanceBulkRecordPayload,
    AttendanceSheetPageProps,
    AttendanceSheetRow,
    AttendanceStatusValue,
} from '@/Types/api/attendance';
import type { PageProps } from '@/Types';

/** حالة الصف في النموذج: القيم الأربع أو '' (لم يُحدَّد بعد). */
export type AttendanceSheetStatus = AttendanceStatusValue | '';

/** صف الورقة كما يراه المكوّن: بيانات الخادم + الحالة المحلية للتعديل. */
export type AttendanceSheetViewRow = Omit<AttendanceSheetRow, 'status' | 'notes'> & {
    status: AttendanceSheetStatus;
    notes: string;
};

/** يطابق App\Enums\AttendanceStatus — نصوص واجهة فقط. */
const STATUS_OPTIONS: { value: AttendanceStatusValue; label: string }[] = [
    { value: 'present', label: 'حاضر' },
    { value: 'absent', label: 'غائب' },
    { value: 'late', label: 'متأخر' },
    { value: 'excused', label: 'مستأذن' },
];

const statusLabels: Record<string, string> = Object.fromEntries(
    STATUS_OPTIONS.map((option) => [option.value, option.label]),
);

const SUMMARY_LABELS: SelectOption[] = [
    ...STATUS_OPTIONS.map((option) => ({ value: option.value, label: option.label })),
    { value: 'unrecorded', label: 'لم يُسجَّل' },
];

/**
 * ViewModel لورقة الحضور اليومية (صفحة المعلم وصفحة الإدارة):
 * يملك الحالات المحلية، الملاحظات، الملخص، حالة الحفظ، والتنقل بين
 * التواريخ/الحلقات. المكوّن يعرض ويُطلق أحداثاً فقط.
 */
export function useAttendanceSheet(
    props: AttendanceSheetPageProps,
    options: { routeName?: string } = {},
) {
    const toast = useToast();
    const routeName = options.routeName ?? 'teacher.attendance.page';

    // الشاشة متاحة بـ attendance.view، أما الحفظ فيتطلب attendance.manage —
    // فلا تُعرض أزرار تنتهي حتماً بـ 403.
    const canManage = computed(() =>
        usePage<PageProps>().props.auth.permissions.includes('attendance.manage'),
    );

    const statuses = reactive<Record<number, AttendanceSheetStatus>>({});
    const notes = reactive<Record<number, string>>({});
    const errors = reactive<FormErrors>({});
    const processing = ref(false);

    function syncFromProps(): void {
        Object.keys(statuses).forEach((key) => delete statuses[Number(key)]);
        Object.keys(notes).forEach((key) => delete notes[Number(key)]);
        Object.keys(errors).forEach((key) => delete errors[key]);

        props.sheet.rows.forEach((row) => {
            statuses[row.student_id] = row.status ?? '';
            notes[row.student_id] = row.notes ?? '';
        });
    }

    syncFromProps();

    // Inertia يعيد استخدام نفس المكوّن بعد الحفظ (redirect back)، فلا تُعاد
    // تهيئة الحالة المحلية تلقائياً: بلا هذه المزامنة يبقى النموذج على
    // تعديلات قديمة ويبدو الحفظ كأنه لم يحدث.
    watch(() => props.sheet, syncFromProps);

    const rows = computed<AttendanceSheetViewRow[]>(() =>
        props.sheet.rows.map((row) => ({
            ...row,
            status: statuses[row.student_id] ?? '',
            notes: notes[row.student_id] ?? '',
        })),
    );

    const summary = computed<Record<string, number>>(() => {
        const counts: Record<string, number> = {
            present: 0,
            absent: 0,
            late: 0,
            excused: 0,
            unrecorded: 0,
        };

        Object.values(statuses).forEach((status) => {
            counts[status === '' ? 'unrecorded' : status] += 1;
        });

        return counts;
    });

    const selectedCircle = computed(
        () => props.sheet.circles.find((circle) => circle.id === props.sheet.circle_id) ?? null,
    );

    /** عدد الطلاب الذين حُدِّدت حالتهم — يُستخدم لتعطيل زر الحفظ. */
    const markedCount = computed(
        () => Object.values(statuses).filter((status) => status !== '').length,
    );

    function setStatus(studentId: number, status: AttendanceStatusValue): void {
        if (!canManage.value) {
            return;
        }

        statuses[studentId] = status;
    }

    function setNote(studentId: number, value: string): void {
        if (!canManage.value) {
            return;
        }

        notes[studentId] = value;
    }

    function markAllPresent(): void {
        if (!canManage.value) {
            return;
        }

        Object.keys(statuses).forEach((key) => {
            statuses[Number(key)] = 'present';
        });
    }

    function clearAll(): void {
        if (!canManage.value) {
            return;
        }

        Object.keys(statuses).forEach((key) => {
            statuses[Number(key)] = '';
        });
    }

    async function save(): Promise<void> {
        if (processing.value || !canManage.value) {
            return;
        }

        if (props.sheet.circle_id === null) {
            toast.error('اختر حلقة أولاً — لا يوجد طلاب لحفظ حضورهم.');

            return;
        }

        // نرسل الصفوف المحدَّدة فقط: الطالب الذي لم تُحدَّد حالته يبقى بلا
        // سجل (unrecorded) ولا يُفترض حضوره تلقائياً.
        const records: AttendanceBulkRecordPayload[] = rows.value
            .filter((row) => row.status !== '')
            .map((row) => ({
                student_id: row.student_id,
                status: row.status as AttendanceStatusValue,
                notes: row.notes.trim() === '' ? null : row.notes.trim(),
            }));

        if (records.length === 0) {
            toast.error('حدّد حالة طالب واحد على الأقل قبل الحفظ.');

            return;
        }

        Object.keys(errors).forEach((key) => delete errors[key]);
        processing.value = true;

        try {
            await AttendanceApi.saveSheet({
                circle_id: props.sheet.circle_id,
                date: props.sheet.date,
                records,
            });

            toast.success(`تم حفظ حضور ${records.length} طالباً بنجاح.`);
        } catch (error) {
            const validationErrors = (error ?? {}) as Record<string, string>;
            Object.entries(validationErrors).forEach(([key, message]) => (errors[key] = message));

            toast.error('تعذّر حفظ الحضور. راجع الحقول المظللة.');
        } finally {
            processing.value = false;
        }
    }

    function onDateChange(event: Event): void {
        const date = (event.target as HTMLInputElement).value;

        AttendanceApi.visitSheet(
            { circle_id: props.sheet.circle_id ?? undefined, date },
            routeName,
        );
    }

    function onCircleChange(event: Event): void {
        const circleId = (event.target as HTMLSelectElement).value;

        AttendanceApi.visitSheet(
            { circle_id: circleId === '' ? undefined : circleId, date: props.sheet.date },
            routeName,
        );
    }

    return {
        rows,
        statuses,
        notes,
        summary,
        summaryLabels: SUMMARY_LABELS,
        statusOptions: STATUS_OPTIONS,
        statusLabels,
        selectedCircle,
        markedCount,
        canManage,
        processing,
        errors,
        setStatus,
        setNote,
        markAllPresent,
        clearAll,
        save,
        onDateChange,
        onCircleChange,
    };
}
