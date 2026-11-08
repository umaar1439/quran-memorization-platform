import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import type { IconName } from '@/Components/AppIcon.vue';
import type { PageProps } from '@/Types';

/**
 * useNavigation — مصدر واحد لعناصر التنقل حسب الدور.
 * كان التعريف مكرراً داخل AuthenticatedLayout (شريط جانبي + درج جوال)،
 * والآن كل الأشرطة (الجانبي، العلوي، شريط الجوال السفلي، الدرج) تقرأ منه،
 * فلا يمكن أن تختلف عناصر التنقل بين شكل وآخر.
 */
export interface NavItem {
    label: string;
    routeName: string;
    icon: IconName;
}

const adminNav: NavItem[] = [
    { label: 'لوحة التحكم', routeName: 'admin.dashboard', icon: 'dashboard' },
    { label: 'المساجد', routeName: 'admin.mosques.index', icon: 'mosque' },
    { label: 'الحلقات', routeName: 'admin.circles.index', icon: 'circles' },
    { label: 'المعلمون', routeName: 'admin.teachers.index', icon: 'user-check' },
    { label: 'الطلاب', routeName: 'admin.students.index', icon: 'graduation-cap' },
    { label: 'المستخدمون', routeName: 'admin.users.index', icon: 'users' },
    { label: 'الإعدادات', routeName: 'admin.settings.index', icon: 'settings' },
    { label: 'Feature Flags', routeName: 'admin.feature-flags.index', icon: 'flag' },
    { label: 'التقارير', routeName: 'admin.reports.page', icon: 'chart' },
    { label: 'سجل التدقيق', routeName: 'admin.audit-logs.page', icon: 'shield-check' },
];

const supervisorNav: NavItem[] = [
    { label: 'لوحة التحكم', routeName: 'admin.dashboard', icon: 'dashboard' },
    { label: 'المساجد', routeName: 'admin.mosques.index', icon: 'mosque' },
    { label: 'الحلقات', routeName: 'admin.circles.index', icon: 'circles' },
    { label: 'المعلمون', routeName: 'admin.teachers.index', icon: 'user-check' },
    { label: 'الطلاب', routeName: 'admin.students.index', icon: 'graduation-cap' },
    { label: 'التقارير', routeName: 'admin.reports.page', icon: 'chart' },
];

const teacherNav: NavItem[] = [
    { label: 'لوحة التحكم', routeName: 'teacher.dashboard', icon: 'dashboard' },
    { label: 'تسميع سريع', routeName: 'teacher.quick-recitation', icon: 'book' },
    { label: 'حلقتي', routeName: 'teacher.my-circle', icon: 'circles' },
    { label: 'الحضور', routeName: 'teacher.attendance.page', icon: 'check-circle' },
];

const studentNav: NavItem[] = [
    { label: 'لوحتي', routeName: 'student.dashboard', icon: 'dashboard' },
    { label: 'أهدافي', routeName: 'student.goals', icon: 'target' },
    { label: 'امتحاناتي', routeName: 'student.exams', icon: 'file-text' },
    { label: 'شهاداتي', routeName: 'student.certificates', icon: 'award' },
];

const parentNav: NavItem[] = [{ label: 'أبنائي', routeName: 'parent.dashboard', icon: 'users' }];

export function useNavigation() {
    const page = usePage<PageProps>();

    const roles = computed<string[]>(() => page.props.auth.roles ?? []);

    const items = computed<NavItem[]>(() => {
        const has = (role: string): boolean => roles.value.includes(role);

        if (has('org-admin') || has('super-admin')) {
            return adminNav;
        }

        if (has('supervisor')) {
            return supervisorNav;
        }

        if (has('teacher')) {
            return teacherNav;
        }

        if (has('student')) {
            return studentNav;
        }

        if (has('parent')) {
            return parentNav;
        }

        return [];
    });

    function isActive(routeName: string): boolean {
        return Boolean(route().current(`${routeName}*`) || route().current(routeName));
    }

    return { items, isActive };
}
