<script setup lang="ts">
import { ref } from 'vue';
import Toast from '@/Components/Toast.vue';
import AppSidebar from '@/Layouts/Partials/AppSidebar.vue';
import AppTopBar from '@/Layouts/Partials/AppTopBar.vue';
import AppMobileNav from '@/Layouts/Partials/AppMobileNav.vue';
import AppMobileDrawer from '@/Layouts/Partials/AppMobileDrawer.vue';

/**
 * تخطيط الصفحات بعد تسجيل الدخول — هيكل متكيّف واحد:
 * - حاسب (lg+): شريط جانبي ثابت + شريط علوي لاصق.
 * - جوال/تابلت: شريط علوي + شريط تنقل سفلي (4 عناصر + المزيد) + درج كامل.
 * الارتفاع بـ dvh (لا 100vh) حتى لا يُقتطع المحتوى خلف شريط المتصفح على الجوال،
 * والمحتوى الرئيسي محجوز له هامش سفلي بمقدار شريط التنقل.
 */
const drawerOpen = ref(false);
</script>

<template>
    <div class="min-h-dvh bg-surface-muted">
        <a
            href="#main-content"
            class="sr-only focus:not-sr-only focus:fixed focus:start-2 focus:top-2 focus:z-toast focus:rounded-lg focus:bg-surface focus:px-4 focus:py-2 focus:text-sm focus:font-semibold focus:text-content focus:shadow-raised focus:ring-2 focus:ring-teal-700"
        >
            تخطَّ إلى المحتوى
        </a>

        <Toast />
        <AppSidebar />
        <AppMobileDrawer :open="drawerOpen" @close="drawerOpen = false" />

        <div class="flex min-h-dvh flex-col lg:ps-64">
            <AppTopBar :menu-open="drawerOpen" @open-menu="drawerOpen = true" />

            <div v-if="$slots.header" class="border-b border-edge bg-surface">
                <div class="mx-auto w-full max-w-7xl px-4 py-4 sm:px-6 sm:py-5 lg:px-8">
                    <slot name="header" />
                </div>
            </div>

            <main
                id="main-content"
                tabindex="-1"
                class="mx-auto w-full max-w-7xl flex-1 px-4 pb-28 pt-4 focus:outline-none sm:px-6 sm:pt-6 md:pb-10 lg:px-8"
            >
                <slot />
            </main>
        </div>

        <AppMobileNav @open-more="drawerOpen = true" />
    </div>
</template>
