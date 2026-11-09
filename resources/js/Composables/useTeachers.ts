import { computed, reactive, ref } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { useToast } from '@/Composables/useToast';
import { TeacherApi } from '@/Services/TeacherApi';
import type { FormErrors, SelectOption } from '@/Types/api/common';
import type {
    TeacherCircleOption,
    TeacherFilters,
    TeacherFormPayload,
    TeacherIndexPageProps,
    TeacherPageModel,
} from '@/Types/api/teacher';
import type { PageProps } from '@/Types';

/** يطابق App\Enums\UserStatus — نصوص واجهة فقط. */
const STATUS_OPTIONS: SelectOption[] = [
    { value: 'active', label: 'نشط' },
    { value: 'inactive', label: 'غير نشط' },
    { value: 'suspended', label: 'موقوف' },
];

function blankForm(): TeacherFormPayload {
    return {
        name: '',
        email: '',
        password: '',
        phone: '',
        specialization: '',
        ijazah_details: '',
        hired_at: '',
        status: 'active',
        circles: [],
    };
}

/** cast 'date' يُسلسَل ISO 8601 كاملاً، وinput[type=date] يريد YYYY-MM-DD. */
function toDateInput(value: string | null): string {
    return value ? value.slice(0, 10) : '';
}

/** اسم الحلقة مع مسجدها للتمييز بين الحلقات المتشابهة في مساجد مختلفة. */
function circleLabel(circle: TeacherCircleOption): string {
    return circle.mosque ? `${circle.name} (${circle.mosque.name})` : circle.name;
}

/**
 * ViewModel لصفحة إدارة المعلمين (Admin/Teachers/Index): حالة النموذج،
 * الإنشاء/التعديل/الحذف، توزيع أخطاء التحقق، اختيار الحلقات، البحث والترقيم،
 * وصلاحيات العرض — والمكوّن يعرض فقط.
 */
export function useTeachers(props: TeacherIndexPageProps) {
    const toast = useToast();

    // الصفحة متاحة لمن يملك teachers.view، أما الكتابة فبصلاحيات منفصلة
    // (create/update/delete) — فلا تُعرض أزرار تنتهي حتمًا بـ 403.
    const canCreate = computed(() =>
        usePage<PageProps>().props.auth.permissions.includes('teachers.create'),
    );
    const canUpdate = computed(() =>
        usePage<PageProps>().props.auth.permissions.includes('teachers.update'),
    );
    const canDelete = computed(() =>
        usePage<PageProps>().props.auth.permissions.includes('teachers.delete'),
    );

    /** الحلقات كخيارات Checkbox (id + تسمية)، مع مسجدها للتمييز. */
    const circleChoices = computed(() =>
        props.circles.map((circle) => ({ id: circle.id, label: circleLabel(circle) })),
    );

    const showModal = ref(false);
    const editingTeacher = ref<TeacherPageModel | null>(null);
    const showDeleteConfirm = ref(false);
    const teacherToDelete = ref<TeacherPageModel | null>(null);
    const processing = ref(false);
    const errors = reactive<FormErrors>({});

    const form = reactive<TeacherFormPayload>(blankForm());

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

    function isCircleSelected(id: number): boolean {
        return form.circles.includes(id);
    }

    function toggleCircle(id: number): void {
        const index = form.circles.indexOf(id);

        if (index === -1) {
            form.circles.push(id);
        } else {
            form.circles.splice(index, 1);
        }
    }


    function openCreateModal(): void {
        editingTeacher.value = null;
        Object.assign(form, blankForm());
        clearErrors();
        showModal.value = true;
    }

    function openEditModal(teacher: TeacherPageModel): void {
        editingTeacher.value = teacher;
        Object.assign(form, {
            name: teacher.user?.name ?? '',
            email: teacher.user?.email ?? '',
            password: '',
            phone: teacher.user?.phone ?? '',
            specialization: teacher.specialization ?? '',
            ijazah_details: teacher.ijazah_details ?? '',
            hired_at: toDateInput(teacher.hired_at),
            status: teacher.user?.status ?? 'active',
            circles: (teacher.user?.circlesTaught ?? []).map((circle) => circle.id),
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
            const payload: TeacherFormPayload = { ...form, circles: [...form.circles] };

            if (editingTeacher.value) {
                await TeacherApi.update(editingTeacher.value.id, payload);
                toast.success('تم تحديث بيانات المعلم بنجاح.');
            } else {
                await TeacherApi.create(payload);
                toast.success('تم إضافة المعلم بنجاح.');
            }

            closeModal();
            editingTeacher.value = null;
        } catch (error) {
            applyErrors(error);
        } finally {
            processing.value = false;
        }
    }

    function confirmDelete(teacher: TeacherPageModel): void {
        teacherToDelete.value = teacher;
        showDeleteConfirm.value = true;
    }

    function cancelDelete(): void {
        showDeleteConfirm.value = false;
        teacherToDelete.value = null;
    }

    async function deleteTeacher(): Promise<void> {
        if (!teacherToDelete.value || processing.value) {
            return;
        }

        clearErrors();
        processing.value = true;

        try {
            await TeacherApi.destroy(teacherToDelete.value.id);
            toast.success('تم حذف المعلم بنجاح.');
            cancelDelete();
        } catch (error) {
            applyErrors(error);
        } finally {
            processing.value = false;
        }
    }

    /** يبني استعلام القائمة الحالي مع تجاوزات، ويحذف القيم الفارغة. */
    function query(overrides: Partial<TeacherFilters> & { page?: number }): TeacherFilters & { page?: number } {
        const merged: TeacherFilters & { page?: number } = { ...props.filters, ...overrides };

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
        TeacherApi.visit(query({ search: value, page: undefined }), true);
    }

    function onPageChange(page: number): void {
        TeacherApi.visit(query({ page }));
    }

    return {
        form,
        errors,
        processing,
        canCreate,
        canUpdate,
        canDelete,
        circleChoices,
        statusOptions: STATUS_OPTIONS,
        showModal,
        editingTeacher,
        showDeleteConfirm,
        isCircleSelected,
        toggleCircle,
        openCreateModal,
        openEditModal,
        closeModal,
        submit,
        confirmDelete,
        cancelDelete,
        deleteTeacher,
        onSearch,
        onPageChange,
    };
}

