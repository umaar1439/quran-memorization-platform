<script setup lang="ts">
import { computed, ref } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import OfflineIndicator from '@/Components/OfflineIndicator.vue';
import Toast from '@/Components/Toast.vue';
import type { PageProps } from '@/Types';

interface NavItem {
    label: string;
    routeName: string;
    href?: string;
}

const page = usePage<PageProps>();
const sidebarOpen = ref(false);

const roles = computed(() => page.props.auth.roles ?? []);
const user = computed(() => page.props.auth.user);

function has(role: string): boolean {
    return roles.value.includes(role);
}

const adminNav: NavItem[] = [
    { label: 'لوحة التحكم', routeName: 'admin.dashboard' },
    { label: 'المساجد', routeName: 'admin.mosques.index' },
    { label: 'الحلقات', routeName: 'admin.circles.index' },
    { label: 'المستخدمون', routeName: 'admin.users.index' },
    { label: 'الإعدادات', routeName: 'admin.settings.index' },
    { label: 'Feature Flags', routeName: 'admin.feature-flags.index' },
    { label: 'التقارير', routeName: 'admin.reports.page' },
    { label: 'سجل التدقيق', routeName: 'admin.audit-logs.page' },
];

const teacherNav: NavItem[] = [
    { label: 'لوحة التحكم', routeName: 'teacher.dashboard' },
    { label: 'تسميع سريع', routeName: 'teacher.quick-recitation' },
    { label: 'حلقتي', routeName: 'teacher.my-circle' },
    { label: 'الحضور', routeName: 'teacher.attendance.page' },
];

const studentNav: NavItem[] = [
    { label: 'لوحتي', routeName: 'student.dashboard' },
    { label: 'أهدافي', routeName: 'student.goals' },
    { label: 'امتحاناتي', routeName: 'student.exams' },
    { label: 'شهاداتي', routeName: 'student.certificates' },
];

const parentNav: NavItem[] = [{ label: 'أبنائي', routeName: 'parent.dashboard' }];

const navItems = computed<NavItem[]>(() => {
    if (has('org-admin') || has('super-admin')) return adminNav;
    if (has('teacher')) return teacherNav;
    if (has('student')) return studentNav;
    if (has('parent')) return parentNav;

    return [];
});

function isActive(routeName: string): boolean {
    return route().current(`${routeName}*`) || route().current(routeName);
}

function logout(): void {
    router.post(route('logout'));
}
</script>

<template>
    <div class="min-h-screen bg-gray-50">
        <Toast />

        <!-- Sidebar (Desktop) -->
        <aside
            class="fixed inset-y-0 right-0 z-30 hidden w-64 flex-col border-l border-gray-200 bg-white lg:flex"
        >
            <div class="flex h-16 items-center gap-2 border-b border-gray-200 px-4">
                <ApplicationLogo class="h-8 w-8 fill-current text-teal-700" />
                <span class="text-sm font-semibold text-gray-800">منصة تحفيظ القرآن</span>
            </div>
            <nav class="flex-1 space-y-1 overflow-y-auto p-3">
                <Link
                    v-for="item in navItems"
                    :key="item.routeName"
                    :href="route(item.routeName)"
                    class="block rounded-md px-3 py-2.5 text-sm font-medium transition"
                    :class="isActive(item.routeName)
                        ? 'bg-teal-50 text-teal-700'
                        : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900'"
                >
                    {{ item.label }}
                </Link>
            </nav>
        </aside>

        <!-- Sidebar (Mobile Drawer) -->
        <div v-if="sidebarOpen" class="fixed inset-0 z-40 lg:hidden">
            <div class="fixed inset-0 bg-black/40" @click="sidebarOpen = false" />
            <aside class="fixed inset-y-0 right-0 w-64 overflow-y-auto bg-white shadow-xl">
                <div class="flex h-16 items-center justify-between border-b border-gray-200 px-4">
                    <span class="text-sm font-semibold text-gray-800">القائمة</span>
                    <button type="button" class="min-h-[44px] min-w-[44px] p-2" @click="sidebarOpen = false">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                <nav class="space-y-1 p-3">
                    <Link
                        v-for="item in navItems"
                        :key="item.routeName"
                        :href="route(item.routeName)"
                        class="block min-h-[44px] rounded-md px-3 py-2.5 text-sm font-medium"
                        :class="isActive(item.routeName)
                            ? 'bg-teal-50 text-teal-700'
                            : 'text-gray-600 hover:bg-gray-100'"
                        @click="sidebarOpen = false"
                    >
                        {{ item.label }}
                    </Link>
                </nav>
            </aside>
        </div>

        <div class="lg:pr-64">
            <!-- Header -->
            <header class="sticky top-0 z-20 flex h-16 items-center justify-between border-b border-gray-200 bg-white px-4">
                <button
                    type="button"
                    class="flex min-h-[44px] min-w-[44px] items-center justify-center rounded-md text-gray-500 hover:bg-gray-100 lg:hidden"
                    @click="sidebarOpen = true"
                >
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>

                <div class="flex items-center gap-3">
                    <OfflineIndicator />
                    <span class="text-sm font-medium text-gray-700">{{ user.name }}</span>
                    <Link
                        :href="route('profile.edit')"
                        class="hidden text-sm text-gray-500 hover:text-gray-700 sm:inline"
                    >
                        الملف الشخصي
                    </Link>
                    <button
                        type="button"
                        class="min-h-[44px] rounded-md px-3 text-sm font-medium text-red-600 hover:bg-red-50"
                        @click="logout"
                    >
                        خروج
                    </button>
                </div>
            </header>

            <header v-if="$slots.header" class="border-b border-gray-200 bg-white">
                <div class="px-4 py-6">
                    <slot name="header" />
                </div>
            </header>

            <main class="p-4">
                <slot />
            </main>
        </div>
    </div>
</template>
