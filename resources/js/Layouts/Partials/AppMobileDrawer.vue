<script setup lang="ts">
import { computed, onMounted, onUnmounted, watch } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import AppIcon from '@/Components/AppIcon.vue';
import AppButton from '@/Components/AppButton.vue';
import BrandMark from '@/Components/BrandMark.vue';
import { useNavigation } from '@/Composables/useNavigation';
import type { PageProps } from '@/Types';

/**
 * درج التنقل على الجوال: القائمة الكاملة (بما يزيد عن أربعة عناصر شريط التنقل
 * السفلي) + الحساب + الخروج. يقفل تمرير الخلفية ويُغلق بمفتاح Escape،
 * ويُغلق تلقائياً عند اختيار أي صفحة.
 */
const props = defineProps<{ open: boolean }>();
const emit = defineEmits<{ close: [] }>();

const page = usePage<PageProps>();
const { items, isActive } = useNavigation();

const user = computed(() => page.props.auth.user);
const initial = computed(() => user.value.name.trim().charAt(0) || 'م');

watch(
    () => props.open,
    (open) => {
        document.body.style.overflow = open ? 'hidden' : '';
    },
);

function closeOnEscape(event: KeyboardEvent): void {
    if (event.key === 'Escape' && props.open) {
        emit('close');
    }
}

onMounted(() => document.addEventListener('keydown', closeOnEscape));

onUnmounted(() => {
    document.removeEventListener('keydown', closeOnEscape);
    document.body.style.overflow = '';
});

function logout(): void {
    emit('close');
    router.post(route('logout'));
}
</script>

<template>
    <Transition
        enter-active-class="transition duration-200 ease-standard"
        enter-from-class="opacity-0"
        enter-to-class="opacity-100"
        leave-active-class="transition duration-150 ease-standard"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
    >
        <div v-if="open" class="fixed inset-0 z-drawer lg:hidden">
            <div class="absolute inset-0 bg-scrim/50 dark:bg-scrim/70" @click="emit('close')" />

            <aside
                id="mobile-drawer"
                class="absolute inset-y-0 start-0 flex w-72 max-w-[85vw] flex-col border-e border-edge bg-surface shadow-pop"
                aria-label="قائمة التنقل"
            >
                <div class="flex h-16 shrink-0 items-center justify-between gap-2 border-b border-edge px-4">
                    <BrandMark href="/" size="sm" label="تحفيظ" />
                    <button
                        type="button"
                        class="focus-ring -me-1 flex h-11 w-11 items-center justify-center rounded-lg text-content-muted hover:bg-surface-sunken"
                        aria-label="إغلاق القائمة"
                        @click="emit('close')"
                    >
                        <AppIcon name="close" size="lg" />
                    </button>
                </div>

                <nav class="flex-1 space-y-1 overflow-y-auto p-3 pb-safe" aria-label="التنقل الرئيسي">
                    <Link
                        v-for="item in items"
                        :key="item.routeName"
                        :href="route(item.routeName)"
                        class="focus-ring flex min-h-11 items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium"
                        :class="isActive(item.routeName)
                            ? 'bg-teal-50 text-teal-800 dark:bg-teal-500/10 dark:text-teal-200'
                            : 'text-content-soft hover:bg-surface-sunken hover:text-content-strong'"
                        :aria-current="isActive(item.routeName) ? 'page' : undefined"
                        @click="emit('close')"
                    >
                        <AppIcon :name="item.icon" class="opacity-80" />
                        <span class="truncate">{{ item.label }}</span>
                    </Link>
                </nav>

                <div class="shrink-0 border-t border-edge p-3 pb-safe">
                    <Link
                        :href="route('profile.edit')"
                        class="focus-ring flex items-center gap-3 rounded-lg p-2 hover:bg-surface-sunken"
                        @click="emit('close')"
                    >
                        <span
                            class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-surface-sunken text-sm font-semibold text-content-body"
                            aria-hidden="true"
                        >
                            {{ initial }}
                        </span>
                        <span class="min-w-0 flex-1">
                            <span class="block truncate text-sm font-medium text-content-strong">{{ user.name }}</span>
                            <span class="block truncate text-xs text-content-muted">الملف الشخصي</span>
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
        </div>
    </Transition>
</template>
