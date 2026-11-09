import { computed, reactive, ref } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import { useToast } from '@/Composables/useToast';
import { CircleApi } from '@/Services/CircleApi';
import type { FormErrors, SelectOption } from '@/Types/api/common';
import type { CircleFormPayload, CircleIndexPageProps, CirclePageModel } from '@/Types/api/circle';
import type { PageProps } from '@/Types';

/** يطابق App\Enums\GenericStatus — نصوص واجهة فقط. */
const STATUS_OPTIONS: SelectOption[] = [
    { value: 'active', label: 'نشط' },
    { value: 'inactive', label: 'غير نشط' },
];

function blankForm(): CircleFormPayload {
    return { mosque_id: '', teacher_id: '', name: '', level: '', capacity: '', status: 'active' };
}

/**
 * ViewModel لصفحة إدارة الحلقات (Admin/Circles/Index): حالة النموذج،
 * التفعيل/الحذف، توزيع أخطاء التحقق، خيارات المساجد/المعلمين، البحث
 * والترقيم، وصلاحية العرض (canManage) — والمكوّن يعرض فقط.
 */
export function useCircles(props: Pick<CircleIndexPageProps, 'filters' | 'mosques' | 'teachers'>) {
    const toast = useToast();

    // الصفحة متاحة لكل من يملك circles.view (بما فيهم المعلم)، لكن الكتابة
    // تتطلب circles.manage — فلا تُعرض أزرار تنتهي بـ 403.
    const canManage = computed(() => usePage<PageProps>().props.auth.permissions.includes('circles.manage'));

    const mosqueOptions = computed<SelectOption[]>(() =>
        props.mosques.map((mosque) => ({ value: mosque.id, label: mosque.name })),
    );

    const teacherOptions = computed<SelectOption[]>(() =>
        props.teachers.map((teacher) => ({ value: teacher.id, label: teacher.name })),
    );

    const showModal = ref(false);
    const editingCircle = ref<CirclePageModel | null>(null);
    const showDeleteConfirm = ref(false);
    const circleToDelete = ref<CirclePageModel | null>(null);
    const processing = ref(false);
    const errors = reactive<FormErrors>({});

    const form = reactive<CircleFormPayload>(blankForm());

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
        editingCircle.value = null;
        Object.assign(form, blankForm());
        clearErrors();
        showModal.value = true;
    }

    function openEditModal(circle: CirclePageModel): void {
        editingCircle.value = circle;
        Object.assign(form, {
            mosque_id: circle.mosque_id,
            teacher_id: circle.teacher_id ?? '',
            name: circle.name,
            level: circle.level ?? '',
            capacity: circle.capacity ?? '',
            status: circle.status,
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
            if (editingCircle.value) {
                await CircleApi.update(editingCircle.value.id, { ...form });
                toast.success('تم تحديث بيانات الحلقة بنجاح.');
            } else {
                await CircleApi.create({ ...form });
                toast.success('تم إضافة الحلقة بنجاح.');
            }

            closeModal();
            editingCircle.value = null;
        } catch (error) {
            applyErrors(error);
        } finally {
            processing.value = false;
        }
    }

    function confirmDelete(circle: CirclePageModel): void {
        circleToDelete.value = circle;
        showDeleteConfirm.value = true;
    }

    function cancelDelete(): void {
        showDeleteConfirm.value = false;
        circleToDelete.value = null;
    }

    async function deleteCircle(): Promise<void> {
        if (!circleToDelete.value || processing.value) {
            return;
        }

        clearErrors();
        processing.value = true;

        try {
            await CircleApi.destroy(circleToDelete.value.id);
            toast.success('تم حذف الحلقة بنجاح.');
            cancelDelete();
        } catch (error) {
            applyErrors(error);
        } finally {
            processing.value = false;
        }
    }

    /** تنقّل Inertia (بحث/ترقيم) — بناء رابط فقط، لا منطق عمل. */
    function onSearch(value: string): void {
        router.get(
            route('admin.circles.index'),
            { ...props.filters, search: value },
            { preserveState: true, replace: true },
        );
    }

    function onPageChange(page: number): void {
        router.get(route('admin.circles.index'), { ...props.filters, page }, { preserveState: true });
    }

    return {
        form,
        errors,
        processing,
        statusOptions: STATUS_OPTIONS,
        canManage,
        mosqueOptions,
        teacherOptions,
        showModal,
        editingCircle,
        showDeleteConfirm,
        openCreateModal,
        openEditModal,
        closeModal,
        submit,
        confirmDelete,
        cancelDelete,
        deleteCircle,
        onSearch,
        onPageChange,
    };
}
