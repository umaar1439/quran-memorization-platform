<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';

defineProps<{
    canLogin?: boolean;
}>();
</script>

<template>
    <Head title="تحفيظ" />

    <div class="min-h-screen bg-gradient-to-b from-emerald-50 via-white to-white text-gray-900 antialiased">
        <!-- شريط علوي -->
        <header class="mx-auto flex max-w-5xl items-center justify-between px-6 py-6">
            <div class="flex items-center gap-2">
                <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-600 text-lg font-bold text-white">
                    ت
                </span>
                <span class="text-lg font-semibold text-emerald-800">تحفيظ</span>
            </div>

            <nav class="flex items-center gap-4 text-sm font-medium">
                <Link
                    v-if="canLogin"
                    :href="route('login')"
                    class="text-gray-700 transition hover:text-emerald-700"
                >
                    تسجيل الدخول
                </Link>
            </nav>
        </header>

        <!-- المحتوى الرئيسي -->
        <main class="mx-auto flex max-w-5xl flex-col items-center px-6 py-16 text-center sm:py-24">
            <span class="mb-4 inline-block rounded-full bg-emerald-100 px-4 py-1 text-sm font-medium text-emerald-700">
                منصة سحابية لإدارة حلقات القرآن الكريم
            </span>

            <h1 class="text-3xl font-extrabold leading-tight text-gray-900 sm:text-5xl">
                تحفيظ<span class="text-emerald-600"> — منصة إدارة حلقات القرآن الكريم</span>
            </h1>

            <p class="mt-6 max-w-2xl text-base leading-relaxed text-gray-600 sm:text-lg">
                إدارة متكاملة للمساجد والحلقات والمعلمين والطلاب، مع تتبّع التسميع والحفظ والمراجعة،
                وإصدار التقارير والشهادات، في بيئة آمنة ومتعددة المستأجرين.
            </p>

            <div class="mt-10 flex flex-wrap items-center justify-center gap-4">
                <Link
                    v-if="canLogin"
                    :href="route('login')"
                    class="rounded-lg bg-emerald-600 px-6 py-3 text-sm font-semibold text-white shadow-md transition hover:bg-emerald-700"
                >
                    تسجيل الدخول
                </Link>
            </div>

            <!-- مزايا مبسطة -->
            <div class="mt-20 grid w-full grid-cols-1 gap-6 text-right sm:grid-cols-3">
                <div class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm">
                    <div class="mb-3 text-2xl">📖</div>
                    <h3 class="mb-1 font-semibold text-gray-900">تتبّع التسميع</h3>
                    <p class="text-sm text-gray-600">تسجيل الحفظ والمراجعة لكل طالب بدقة وسهولة.</p>
                </div>
                <div class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm">
                    <div class="mb-3 text-2xl">🕌</div>
                    <h3 class="mb-1 font-semibold text-gray-900">إدارة الحلقات</h3>
                    <p class="text-sm text-gray-600">تنظيم المساجد والحلقات والمعلمين في مكان واحد.</p>
                </div>
                <div class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm">
                    <div class="mb-3 text-2xl">🏆</div>
                    <h3 class="mb-1 font-semibold text-gray-900">شهادات وتقارير</h3>
                    <p class="text-sm text-gray-600">إصدار الشهادات وتقارير الأداء بشكل آلي وموثّق.</p>
                </div>
            </div>
        </main>

        <footer class="mx-auto max-w-5xl px-6 py-8 text-center text-sm text-gray-400">
            &copy; تحفيظ. جميع الحقوق محفوظة.
        </footer>
    </div>
</template>
