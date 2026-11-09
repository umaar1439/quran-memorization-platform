import { computed, reactive, ref } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { useToast } from '@/Composables/useToast';
import { StudentApi } from '@/Services/StudentApi';
import type { FormErrors, SelectOption } from '@/Types/api/common';
import type {
    StudentFilterKey,
    StudentFilters,
    StudentFormPayload,
    StudentIndexPageProps,
    StudentPageModel,
} from '@/Types/api/student';
import type { PageProps } from '@/Types';

/** يطابق App\Enums\StudentStatus — نصوص واجهة فقط. */
const STATUS_OPTIONS: SelectOption[] = [
    { value: 'active', label: 'نشط' },
    { value: 'transferred', label: 'منقول' },
    { value: 'graduated', label: 'متخرج' },
    { value: 'withdrawn', label: 'منسحب' },
];

function blankForm(): StudentFormPayload {
    return {
        name: '',
        email: '',
        password: '',
        enrollment_number: '',
        birth_date: '',
        current_juz: '',
        circle_id: '',
        status: 'active',
    };
}

/** cast 'date' يُسلسَل ISO 8601 كاملاً، وinput[type=date] يريد YYYY-MM-DD. */
function toDateInput(value: string | null): string {
    return value ? value.slice(0, 10) : '';
}

/**
 * ViewModel لصفحة إدارة الطلاب (Admin/Students/Index): حالة النموذج،
 * الإنشاء/التعديل/الحذف، توزيع أخطاء التحقق، خيارات الحلقة/المسجد/الحالة،
 * الفلترة والبحث والترقيم، وصلاحية العرض (canManage) — والمكوّن يعرض فقط.
 */
export function useStudents(props: StudentIndexPageProps) {
    const toast = useToast();

    // الصفحة متاحة لمن يملك students.view، أما الكتابة فتتطلب students.manage
    // — فلا تُعرض أزرار تنتهي حتماً بـ 403.
    const canManage = computed(() =>
        usePage<PageProps>().props.auth.permissions.includes('students.manage'),
    );

    const circleOptions = computed<SelectOption[]>(() =>
        props.circles.map((circle) => ({
            value: circle.id,
            label: circle.mosque ? `${circle.name} (${circle.mosque.name})` : circle.name,
        })),
    );

    const mosqueOptions = computed<SelectOption[]>(() =>
        props.mosques.map((mosque) => ({ value: mosque.id, label: mosque.name })),
    );

    /** حقول شريط الفلاتر — المكوّن يعرضها بـ v-for بلا منطق. */
    const filterFields = computed<
        { key: StudentFilterKey; label: string; allLabel: string; options: SelectOption[] }[]
    >(() => [
        { key: 'circle_id', label: 'الحلقة', allLabel: 'كل الحلقات', options: circleOptions.value },
        { key: 'mosque_id', label: 'المسجد', allLabel: 'كل المساجد', options: mosqueOptions.value },
        { key: 'status', label: 'الحالة', allLabel: 'كل الحالات', options: STATUS_OPTIONS },
    ]);

    const showModal = ref(false);
    const editingStudent = ref<StudentPageModel | null>(null);
    const showDeleteConfirm = ref(false);
    const studentToDelete = ref<StudentPageModel | null>(null);
    const processing = ref(false);
    const errors = reactive<FormErrors>({});

    const form = reactive<StudentFormPayload>(blankForm());

    function clearErrors(): void {
        Object.keys(errors).forEach((key) => delete errors[key]);
    }

    /**
     * Inertia يرفض الـ Promise بخريطة أخطاء التحقق فقط؛ أي شكل آخر (خطأ
     * شبكة/استثناء) يظهر عبر معالج Inertia العام، فلا نُخفيه هنا.
     */
    function applyErrors(error: unknown): void {
        const validationErrors = (error ?? {}) as Record<string, string>;

        Object.entries(validationErrors).forEach(([field, message]) => {
            errors[field] = String(message);
        });
    }

    function openCreateModal(): void {
        editingStudent.value = null;
        Object.assign(form, blankForm());
        clearErrors();
        showModal.value = true;
    }

    function openEditModal(student: StudentPageModel): void {
        editingStudent.value = student;
        Object.assign(form, {
            name: student.user?.name ?? '',
            email: student.user?.email ?? '',
            password: '',
            enrollment_number: student.enrollment_number ?? '',
            birth_date: toDateInput(student.birth_date),
            current_juz: student.current_juz ?? '',
            circle_id: student.circle_id ?? '',
            status: student.status,
        });
        clearErrors();
        showModal.value = true;
    }

    function closeModal(): void {
        showModal.value = false;
    }

    async function submit(): Promise<void> {
        if (processing.value) {
            return;
        }

        clearErrors();
        processing.value = true;

        try {
            const payload = { ...form };

            if (editingStudent.value) {
                await StudentApi.update(editingStudent.value.id, payload);
                toast.success('تم تحديث بيانات الطالب بنجاح.');
            } else {
                await StudentApi.create(payload);
                toast.success('تم إضافة الطالب بنجاح.');
            }

            closeModal();
            editingStudent.value = null;
        } catch (error) {
            applyErrors(error);
        } finally {
            processing.value = false;
        }
    }

    function confirmDelete(student: StudentPageModel): void {
        studentToDelete.value = student;
        showDeleteConfirm.value = true;
    }

    function cancelDelete(): void {
        showDeleteConfirm.value = false;
        studentToDelete.value = null;
    }

    async function deleteStudent(): Promise<void> {
        if (!studentToDelete.value || processing.value) {
            return;
        }

        clearErrors();
        processing.value = true;

        try {
            await StudentApi.destroy(studentToDelete.value.id);
            toast.success('تم حذف الطالب بنجاح.');
            cancelDelete();
        } catch (error) {
            applyErrors(error);
        } finally {
            processing.value = false;
        }
    }

    /** يبني استعلام القائمة الحالي مع تجاوزات، ويحذف القيم الفارغة. */
    function query(overrides: Partial<StudentFilters> & { page?: number }): StudentFilters & { page?: number } {
        const merged: StudentFilters & { page?: number } = { ...props.filters, ...overrides };

        (Object.keys(merged) as (keyof typeof merged)[]).forEach((key) => {
            const value = merged[key];

            if (value === undefined || value === null || value === '') {
                delete merged[key];
            }
        });

        return merged;
    }

    /** تنقّل Inertia (بحث) — بناء رابط فقط، لا منطق عمل. */
    function onSearch(value: string): void {
        StudentApi.visit(query({ search: value, page: undefined }), true);
    }

    function onPageChange(page: number): void {
        StudentApi.visit(query({ page }));
    }

    /** تغيير أحد فلاتر الحلقة/المسجد/الحالة — يعيد الترقيم إلى الصفحة الأولى. */
    function onFilterChange(key: StudentFilterKey, event: Event): void {
        const value = (event.target as HTMLSelectElement).value;
        const overrides: Partial<StudentFilters> & { page?: number } = { page: undefined };
        overrides[key] = value === '' ? undefined : value;

        StudentApi.visit(query(overrides));
    }

    return {
        form,
        errors,
        processing,
        filters: props.filters,
        canManage,
        circleOptions,
        mosqueOptions,
        statusOptions: STATUS_OPTIONS,
        filterFields,
        showModal,
        editingStudent,
        showDeleteConfirm,
        openCreateModal,
        openEditModal,
        closeModal,
        submit,
        confirmDelete,
        cancelDelete,
        deleteStudent,
        onSearch,
        onPageChange,
        onFilterChange,
    };
}
