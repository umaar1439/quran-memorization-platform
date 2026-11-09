import { computed, reactive, ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { useToast } from '@/Composables/useToast';
import { UserApi } from '@/Services/UserApi';
import type { FormErrors, UserFormPayload, UserIndexPageProps, UserPageModel } from '@/Types/api/user';

/** أسماء الأدوار — نصوص واجهة فقط، والمصدر البرمجي هو Seeder. */
const ROLE_LABELS: Record<string, string> = {
    'org-admin': 'مدير المنظمة',
    supervisor: 'مشرف',
    teacher: 'معلم',
    student: 'طالب',
    parent: 'ولي أمر',
};

const STATUS_OPTIONS: { value: string; label: string }[] = [
    { value: 'active', label: 'نشط' },
    { value: 'inactive', label: 'غير نشط' },
    { value: 'suspended', label: 'موقوف' },
];

function blankForm(): UserFormPayload {
    return { name: '', email: '', password: '', phone: '', role: '', status: 'active' };
}

/**
 * ViewModel لصفحة إدارة المستخدمين (Admin/Users/Index): حالة النموذج
 * والتفعيل/الحذف، توزيع أخطاء التحقق، البحث والترقيم، والرسائل (Toast).
 * المكوّن يعرض فقط، وكل نداء شبكة في UserApi.
 */
export function useUserManagement(props: Pick<UserIndexPageProps, 'filters' | 'availableRoles'>) {
    const toast = useToast();

    const roleOptions = computed(() =>
        props.availableRoles.map((role) => ({ value: role, label: ROLE_LABELS[role] ?? role })),
    );

    const showModal = ref(false);
    const editingUser = ref<UserPageModel | null>(null);
    const showDeleteConfirm = ref(false);
    const userToDelete = ref<UserPageModel | null>(null);
    const processing = ref(false);
    const errors = reactive<FormErrors>({});

    const form = reactive<UserFormPayload>(blankForm());

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

    function roleLabel(role: string | undefined): string {
        if (!role) {
            return '—';
        }

        return ROLE_LABELS[role] ?? role;
    }

    function openCreateModal(): void {
        editingUser.value = null;
        Object.assign(form, blankForm());
        clearErrors();
        showModal.value = true;
    }

    function openEditModal(user: UserPageModel): void {
        editingUser.value = user;
        Object.assign(form, {
            name: user.name,
            email: user.email,
            password: '',
            phone: user.phone ?? '',
            role: user.roles[0]?.name ?? '',
            status: user.status,
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
            if (editingUser.value) {
                await UserApi.update(editingUser.value.id, { ...form });
                toast.success('تم تحديث بيانات المستخدم بنجاح.');
            } else {
                await UserApi.create({ ...form });
                toast.success('تم إضافة المستخدم بنجاح.');
            }

            closeModal();
            editingUser.value = null;
        } catch (error) {
            applyErrors(error);
        } finally {
            processing.value = false;
        }
    }

    function confirmDelete(user: UserPageModel): void {
        userToDelete.value = user;
        showDeleteConfirm.value = true;
    }

    function cancelDelete(): void {
        showDeleteConfirm.value = false;
        userToDelete.value = null;
    }

    async function deleteUser(): Promise<void> {
        if (!userToDelete.value || processing.value) {
            return;
        }

        processing.value = true;

        try {
            await UserApi.destroy(userToDelete.value.id);
            toast.success('تم حذف المستخدم بنجاح.');
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
            route('admin.users.index'),
            { ...props.filters, search: value },
            { preserveState: true, replace: true },
        );
    }

    function onPageChange(page: number): void {
        router.get(route('admin.users.index'), { ...props.filters, page }, { preserveState: true });
    }

    return {
        form,
        errors,
        processing,
        roleOptions,
        statusOptions: STATUS_OPTIONS,
        showModal,
        editingUser,
        showDeleteConfirm,
        roleLabel,
        openCreateModal,
        openEditModal,
        closeModal,
        submit,
        confirmDelete,
        cancelDelete,
        deleteUser,
        onSearch,
        onPageChange,
    };
}
