import { reactive, ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { useToast } from '@/Composables/useToast';
import { MosqueApi } from '@/Services/MosqueApi';
import type { FormErrors, SelectOption } from '@/Types/api/common';
import type { MosqueFormPayload, MosqueIndexPageProps, MosquePageModel } from '@/Types/api/mosque';

/** يطابق App\Enums\GenericStatus — نصوص واجهة فقط. */
const STATUS_OPTIONS: SelectOption[] = [
    { value: 'active', label: 'نشط' },
    { value: 'inactive', label: 'غير نشط' },
];

function blankForm(): MosqueFormPayload {
    return { name: '', address: '', city: '', phone: '', status: 'active' };
}

/**
 * ViewModel لصفحة إدارة المساجد (Admin/Mosques/Index): حالة النموذج،
 * التفعيل/الحذف، توزيع أخطاء التحقق، البحث والترقيم، والرسائل (Toast).
 * المكوّن يعرض فقط، وكل نداء شبكة في MosqueApi.
 */
export function useMosques(props: Pick<MosqueIndexPageProps, 'filters'>) {
    const toast = useToast();

    const showModal = ref(false);
    const editingMosque = ref<MosquePageModel | null>(null);
    const showDeleteConfirm = ref(false);
    const mosqueToDelete = ref<MosquePageModel | null>(null);
    const processing = ref(false);
    const errors = reactive<FormErrors>({});

    const form = reactive<MosqueFormPayload>(blankForm());

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
        editingMosque.value = null;
        Object.assign(form, blankForm());
        clearErrors();
        showModal.value = true;
    }

    function openEditModal(mosque: MosquePageModel): void {
        editingMosque.value = mosque;
        Object.assign(form, {
            name: mosque.name,
            address: mosque.address ?? '',
            city: mosque.city ?? '',
            phone: mosque.phone ?? '',
            status: mosque.status,
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
            if (editingMosque.value) {
                await MosqueApi.update(editingMosque.value.id, { ...form });
                toast.success('تم تحديث بيانات المسجد بنجاح.');
            } else {
                await MosqueApi.create({ ...form });
                toast.success('تم إضافة المسجد بنجاح.');
            }

            closeModal();
            editingMosque.value = null;
        } catch (error) {
            applyErrors(error);
        } finally {
            processing.value = false;
        }
    }

    function confirmDelete(mosque: MosquePageModel): void {
        mosqueToDelete.value = mosque;
        showDeleteConfirm.value = true;
    }

    function cancelDelete(): void {
        showDeleteConfirm.value = false;
        mosqueToDelete.value = null;
    }

    async function deleteMosque(): Promise<void> {
        if (!mosqueToDelete.value || processing.value) {
            return;
        }

        clearErrors();
        processing.value = true;

        try {
            await MosqueApi.destroy(mosqueToDelete.value.id);
            toast.success('تم حذف المسجد بنجاح.');
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
            route('admin.mosques.index'),
            { ...props.filters, search: value },
            { preserveState: true, replace: true },
        );
    }

    function onPageChange(page: number): void {
        router.get(route('admin.mosques.index'), { ...props.filters, page }, { preserveState: true });
    }

    return {
        form,
        errors,
        processing,
        statusOptions: STATUS_OPTIONS,
        showModal,
        editingMosque,
        showDeleteConfirm,
        openCreateModal,
        openEditModal,
        closeModal,
        submit,
        confirmDelete,
        cancelDelete,
        deleteMosque,
        onSearch,
        onPageChange,
    };
}
