<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import BrandMark from '@/Components/BrandMark.vue';
import AppButton from '@/Components/AppButton.vue';
import AppIcon, { type IconName } from '@/Components/AppIcon.vue';
import ThemeToggle from '@/Components/ThemeToggle.vue';

defineProps<{
    canLogin?: boolean;
}>();

interface Feature {
    icon: IconName;
    title: string;
    description: string;
}

const features: Feature[] = [
    { icon: 'book', title: 'تتبّع التسميع', description: 'تسجيل الحفظ والمراجعة لكل طالب بدقة وسهولة.' },
    { icon: 'circles', title: 'إدارة الحلقات', description: 'تنظيم المساجد والحلقات والمعلمين في مكان واحد.' },
    { icon: 'award', title: 'شهادات وتقارير', description: 'إصدار الشهادات وتقارير الأداء بشكل آلي وموثّق.' },
];
</script>

<template>
    <Head title="تحفيظ" />

    <div
        class="min-h-dvh bg-gradient-to-b from-teal-50 via-surface-muted to-surface-muted dark:from-teal-500/10 dark:via-surface-muted dark:to-surface-muted"
    >
        <header class="sticky top-0 z-nav border-b border-edge bg-surface/90 backdrop-blur pt-safe">
            <div class="mx-auto flex h-16 w-full max-w-6xl items-center justify-between gap-3 px-4 sm:px-6 lg:px-8">
                <BrandMark href="/" label="تحفيظ" />
                <nav class="flex items-center gap-2" aria-label="روابط الدخول">
                    <ThemeToggle />
                    <AppButton v-if="canLogin" :href="route('login')" size="sm">تسجيل الدخول</AppButton>
                </nav>
            </div>
        </header>

        <main class="mx-auto w-full max-w-6xl px-4 pb-16 pt-12 text-center sm:px-6 sm:pt-20 lg:px-8">
            <span
                class="inline-flex items-center gap-2 rounded-full bg-teal-100 px-4 py-1 text-sm font-medium text-teal-800 dark:bg-teal-500/15 dark:text-teal-200"
            >
                <AppIcon name="sparkles" size="sm" />
                منصة سحابية لإدارة حلقات القرآن الكريم
            </span>

            <h1 class="mx-auto mt-5 max-w-3xl text-3xl font-extrabold leading-tight text-content-strong sm:text-5xl">
                تحفيظ — إدارة حلقات القرآن الكريم
            </h1>

            <p class="mx-auto mt-5 max-w-2xl text-base leading-relaxed text-content-soft sm:text-lg">
                إدارة متكاملة للمساجد والحلقات والمعلمين والطلاب، مع تتبّع التسميع والحفظ والمراجعة،
                وإصدار التقارير والشهادات، في بيئة آمنة ومتعددة المستأجرين.
            </p>

            <div class="mt-8 flex flex-wrap items-center justify-center gap-3">
                <AppButton v-if="canLogin" :href="route('login')" size="lg">تسجيل الدخول</AppButton>
            </div>

            <ul class="mt-14 grid w-full grid-cols-1 gap-4 text-start sm:grid-cols-3 sm:gap-6">
                <li v-for="feature in features" :key="feature.title" class="card p-5 sm:p-6">
                    <span
                        class="mb-3 flex h-11 w-11 items-center justify-center rounded-xl bg-teal-50 text-teal-700 dark:bg-teal-500/10 dark:text-teal-300"
                    >
                        <AppIcon :name="feature.icon" size="lg" />
                    </span>
                    <h2 class="font-semibold text-content-strong">{{ feature.title }}</h2>
                    <p class="mt-1 text-sm leading-relaxed text-content-soft">{{ feature.description }}</p>
                </li>
            </ul>
        </main>

        <footer class="border-t border-edge py-6 text-center text-sm text-content-muted">
            &copy; تحفيظ. جميع الحقوق محفوظة.
        </footer>
    </div>
</template>
