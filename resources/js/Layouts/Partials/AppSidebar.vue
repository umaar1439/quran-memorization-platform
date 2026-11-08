<script setup lang="ts">
import { computed } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import AppIcon from '@/Components/AppIcon.vue';
import AppButton from '@/Components/AppButton.vue';
import BrandMark from '@/Components/BrandMark.vue';
import { useNavigation } from '@/Composables/useNavigation';
import type { PageProps } from '@/Types';

/**
 * الشريط الجانبي على الحاسب (lg+). يستخدم خصائص منطقية (end/border-s)
 * فيعمل في RTL وLTR دون تعديل، ويمرّر قائمة التنقل من useNavigation.
 */
const page = usePage<PageProps>();
const { items, isActive } = useNavigation();

const user = computed(() => page.props.auth.user);
const initial = computed(() => user.value.name.trim().charAt(0) || 'م');

function logout(): void {
    router.post(route('logout'));
}
</script>

<template>
    <aside
        class="fixed inset-y-0 start-0 z-nav hidden w-64 flex-col border-e border-edge bg-surface lg:flex"
    >
        <div class="flex h-16 shrink-0 items-center border-b border-edge px-5">
            <BrandMark href="/" label="منصة تحفيظ القرآن" />
        </div>

        <nav class="flex-1 space-y-1 overflow-y-auto p-3" aria-label="التنقل الرئيسي">
            <Link
                v-for="item in items"
                :key="item.routeName"
                :href="route(item.routeName)"
                class="focus-ring flex min-h-11 items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors duration-150"
                :class="isActive(item.routeName)
                    ? 'bg-teal-50 text-teal-800 dark:bg-teal-500/10 dark:text-teal-200'
                    : 'text-content-soft hover:bg-surface-sunken hover:text-content-strong'"
                :aria-current="isActive(item.routeName) ? 'page' : undefined"
            >
                <AppIcon :name="item.icon" class="opacity-80" />
                <span class="truncate">{{ item.label }}</span>
            </Link>

            <p v-if="items.length === 0" class="px-3 py-2 text-sm text-content-muted">
                لا توجد صفحات متاحة لهذا الحساب.
            </p>
        </nav>

        <div class="shrink-0 border-t border-edge p-3">
            <Link
                :href="route('profile.edit')"
                class="focus-ring flex items-center gap-3 rounded-lg p-2 hover:bg-surface-sunken"
            >
                <span
                    class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-surface-sunken text-sm font-semibold text-content-body"
                    aria-hidden="true"
                >
                    {{ initial }}
                </span>
                <span class="min-w-0 flex-1">
                    <span class="block truncate text-sm font-medium text-content-strong">{{ user.name }}</span>
                    <span class="block truncate text-xs text-content-muted">{{ user.email }}</span>
                </span>
            </Link>
            <AppButton
                variant="ghost"
                size="sm"
                icon="logout"
                block
                class="mt-1"
                @click="logout"
            >
                تسجيل الخروج
            </AppButton>
        </div>
    </aside>
</template>
