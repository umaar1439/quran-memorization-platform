import { computed, reactive, ref, watch } from 'vue';
import { useToast } from '@/Composables/useToast';
import { RoleApi } from '@/Services/RoleApi';
import type {
    FormErrors,
    PermissionGroup,
    RoleIndexPageProps,
    RoleMatrixRow,
} from '@/Types/api/user';

/** أسماء الأدوار ومجموعات الصلاحيات — نصوص واجهة فقط، والمصدر البرمجي هو Seeder. */
const ROLE_LABELS: Record<string, string> = {
    'super-admin': 'مدير النظام العام',
    'org-admin': 'مدير المنظمة',
    supervisor: 'مشرف',
    teacher: 'معلم',
    student: 'طالب',
    parent: 'ولي أمر',
};

const GROUP_LABELS: Record<string, string> = {
    users: 'المستخدمون',
    roles: 'الأدوار والصلاحيات',
    teachers: 'المعلمون',
    circles: 'الحلقات',
    students: 'الطلاب',
    recitations: 'التسميع',
    attendance: 'الحضور',
    exams: 'الامتحانات',
    certificates: 'الشهادات',
    reports: 'التقارير',
    settings: 'الإعدادات',
    terminology: 'المصطلحات',
    features: 'أعلام الميزات',
};

const PERMISSION_LABELS: Record<string, string> = {
    'users.view': 'عرض المستخدمين',
    'users.create': 'إضافة مستخدم',
    'users.update': 'تعديل مستخدم',
    'users.delete': 'حذف مستخدم',
    'roles.view': 'عرض الأدوار والصلاحيات',
    'roles.manage': 'تعديل صلاحيات الأدوار',
    'teachers.view': 'عرض المعلمين',
    'teachers.create': 'إضافة معلم',
    'teachers.update': 'تعديل معلم',
    'teachers.delete': 'حذف معلم',
    'circles.view': 'عرض الحلقات',
    'circles.manage': 'إدارة الحلقات',
    'students.view': 'عرض الطلاب',
    'students.manage': 'إدارة الطلاب',
    'students.transfer': 'عرض نقل الطلاب',
    'recitations.create': 'تسجيل تسميع',
    'recitations.view': 'عرض التسميعات',
    'recitations.update': 'تعديل التسميع',
    'attendance.view': 'عرض الحضور',
    'exams.view': 'عرض الامتحانات',
    'exams.create': 'إنشاء امتحان وإدخال نتيجة',
    'exams.approve': 'اعتماد نتائج الامتحانات',
    'certificates.generate': 'توليد الشهادات',
    'reports.generate': 'توليد التقارير',
    'reports.download': 'تنزيل التقارير',
    'settings.manage': 'إدارة الإعدادات',
    'terminology.manage': 'إدارة المصطلحات',
    'features.manage': 'إدارة أعلام الميزات',
};

/**
 * ViewModel لشاشة الأدوار والصلاحيات (Admin/Roles/Index): الدور المختار،
 * مسودة الصلاحيات، تجميع الصلاحيات للعرض، والحفظ. المكوّن يعرض فقط، وكل
 * نداء شبكة في RoleApi. القواعد الأمنية (منع التصعيد/حماية super-admin)
 * تُفرض على الخادم ولا تُكرَّر هنا.
 */
export function useRolePermissions(props: RoleIndexPageProps) {
    const toast = useToast();

    const errors = reactive<FormErrors>({});
    const processing = ref(false);

    const selectedRoleId = ref<number | null>(
        props.roles.find((role) => role.editable)?.id ?? props.roles[0]?.id ?? null,
    );

    const selectedRole = computed<RoleMatrixRow | null>(
        () => props.roles.find((role) => role.id === selectedRoleId.value) ?? null,
    );

    const canEdit = computed<boolean>(() => selectedRole.value?.editable === true);

    /** مسودة قابلة للتعديل قبل الحفظ — لا نعدّل props الخادم مباشرة. */
    const draft = ref<string[]>([]);

    // مزامنة المسودة مع الدور المختار (أو مع بيانات الخادم بعد الحفظ).
    watch(
        selectedRole,
        (role) => {
            draft.value = role ? [...role.permissions] : [];
            clearErrors();
        },
        { immediate: true },
    );

    const selectedCount = computed<number>(() => draft.value.length);

    const groups = computed<PermissionGroup[]>(() => {
        const result: PermissionGroup[] = [];

        for (const name of props.permissions) {
            const key = name.split('.')[0] ?? name;
            let group = result.find((item) => item.key === key);

            if (!group) {
                group = { key, label: GROUP_LABELS[key] ?? key, permissions: [] };
                result.push(group);
            }

            group.permissions.push({ name, label: PERMISSION_LABELS[name] ?? name });
        }

        return result;
    });

    function clearErrors(): void {
        Object.keys(errors).forEach((key) => delete errors[key]);
    }

    function roleLabel(role: string): string {
        return ROLE_LABELS[role] ?? role;
    }

    function isChecked(name: string): boolean {
        return draft.value.includes(name);
    }

    function toggle(name: string): void {
        draft.value = isChecked(name)
            ? draft.value.filter((permission) => permission !== name)
            : [...draft.value, name];
    }

    function groupNames(group: PermissionGroup): string[] {
        return group.permissions.map((permission) => permission.name);
    }

    function isGroupChecked(group: PermissionGroup): boolean {
        const names = groupNames(group);

        return names.length > 0 && names.every((name) => isChecked(name));
    }

    function groupCheckedCount(group: PermissionGroup): number {
        return group.permissions.filter((permission) => isChecked(permission.name)).length;
    }

    function toggleGroup(group: PermissionGroup): void {
        const names = groupNames(group);

        draft.value = isGroupChecked(group)
            ? draft.value.filter((permission) => !names.includes(permission))
            : Array.from(new Set([...draft.value, ...names]));
    }

    async function submit(): Promise<void> {
        if (!selectedRole.value || !canEdit.value || processing.value) {
            return;
        }

        clearErrors();
        processing.value = true;

        try {
            await RoleApi.updatePermissions(selectedRole.value.id, [...draft.value]);
            toast.success('تم تحديث صلاحيات الدور بنجاح.');
        } catch (error) {
            const validationErrors = (error ?? {}) as Record<string, string>;

            Object.entries(validationErrors).forEach(([field, message]) => {
                errors[field] = String(message);
            });
        } finally {
            processing.value = false;
        }
    }

    return {
        selectedRoleId,
        selectedRole,
        canEdit,
        groups,
        selectedCount,
        errors,
        processing,
        roleLabel,
        isChecked,
        toggle,
        isGroupChecked,
        groupCheckedCount,
        toggleGroup,
        submit,
    };
}
