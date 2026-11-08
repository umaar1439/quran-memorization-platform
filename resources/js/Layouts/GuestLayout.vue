<script setup lang="ts">
import BrandMark from '@/Components/BrandMark.vue';
import ThemeToggle from '@/Components/ThemeToggle.vue';

/**
 * تخطيط صفحات المصادقة: بطاقة واحدة متمركزة على خلفية هادئة،
 * بارتفاع dvh (يعمل مع لوحة مفاتيح الجوال) وبمناطق آمنة،
 * وبعرض أقصى مقروء بدل تمدد الحقول على الشاشات الكبيرة.
 * مفتاح الثيم في أعلى العمود (خارج البطاقة) حتى يتاح قبل تسجيل الدخول.
 */
</script>

<template>
    <div
        class="flex min-h-dvh flex-col items-center justify-center bg-gradient-to-b from-teal-50 via-surface-muted to-surface-muted px-4 pb-safe pt-safe sm:px-6 dark:from-teal-500/10 dark:via-surface-muted dark:to-surface-muted"
    >
        <main class="w-full max-w-md py-8 sm:py-10">
            <div class="mb-4 flex justify-end">
                <ThemeToggle />
            </div>

            <div class="mb-6 flex justify-center">
                <BrandMark href="/" size="lg" label="تحفيظ" />
            </div>

            <div class="card p-6 shadow-raised sm:p-8">
                <slot />
            </div>

            <p class="mt-6 text-center text-xs text-content-muted">
                منصة إدارة حلقات تحفيظ القرآن الكريم
            </p>
        </main>
    </div>
</template>

