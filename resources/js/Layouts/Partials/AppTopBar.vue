<script setup lang="ts">
import { computed } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import AppIcon from '@/Components/AppIcon.vue';
import AppButton from '@/Components/AppButton.vue';
import BrandMark from '@/Components/BrandMark.vue';
import OfflineIndicator from '@/Components/OfflineIndicator.vue';
import PwaInstallButton from '@/Components/PwaInstallButton.vue';
import ThemeToggle from '@/Components/ThemeToggle.vue';
import type { PageProps } from '@/Types';

/**
 * الشريط العلوي: لاصق (sticky) وشفاف قليلاً مع blur خلفي حتى يبقى السياق
 * ظاهراً عند التمرير. الإجراءات الأساسية فقط (حساب + خروج)؛ بقية التنقل
 * في الشريط الجانبي أو الشريط السفلي على الجوال.
 */
defineProps<{
    menuOpen: boolean;
}>();

const emit = defineEmits<{ 'open-menu': [] }>();

const page = usePage<PageProps>();
const user = computed(() => page.props.auth.user);
const initial = computed(() => user.value.name.trim().charAt(0) || 'م');

function logout(): void {
    router.post(route('logout'));
}
</script>

<template>
    <header
        class="sticky top-0 z-nav flex h-16 items-center justify-between gap-3 border-b border-edge bg-surface/95 px-4 backdrop-blur sm:px-6 lg:px-8"
    >
        <div class="flex min-w-0 items-center gap-2">
            <!-- زر القائمة: على الجوال فقط، وللدرج الكامل -->
            <button
                type="button"
                class="focus-ring -ms-1 flex h-11 w-11 items-center justify-center rounded-lg text-content-body hover:bg-surface-sunken lg:hidden"
                :aria-expanded="menuOpen"
                aria-controls="mobile-drawer"
                aria-label="فتح قائمة التنقل"
                @click="emit('open-menu')"
            >
                <AppIcon name="menu" size="lg" />
            </button>

            <BrandMark href="/" size="sm" class="lg:hidden" />
        </div>

        <div class="flex items-center gap-1 sm:gap-2">
            <PwaInstallButton />

            <ThemeToggle />

            <OfflineIndicator />

            <Link
                :href="route('profile.edit')"
                class="focus-ring flex min-h-11 items-center gap-2 rounded-lg px-2 text-sm font-medium text-content-body hover:bg-surface-sunken"
            >
                <span
                    class="flex h-8 w-8 items-center justify-center rounded-full bg-surface-sunken text-xs font-semibold text-content-body"
                    aria-hidden="true"
                >
                    {{ initial }}
                </span>
                <!-- الاسم الكامل مخفي على الجوال لتوفير المساحة، ويظهر من sm فأعلى -->
                <span class="hidden max-w-[10rem] truncate sm:inline">{{ user.name }}</span>
            </Link>

            <AppButton variant="ghost" size="sm" icon="logout" aria-label="تسجيل الخروج" @click="logout">
                <span class="hidden sm:inline">خروج</span>
            </AppButton>
        </div>
    </header>
</template>
