<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div class="flex min-h-screen flex-col items-center justify-center bg-gradient-to-b from-emerald-50 dark:from-emerald-500/10 via-surface to-emerald-50 dark:to-emerald-500/10 px-4 py-8">
        <div class="mb-6">
            <Link href="/" class="flex items-center gap-2">
                <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-emerald-600 text-xl font-bold text-white">ت</span>
                <span class="text-xl font-bold text-emerald-800 dark:text-emerald-200">تحفيظ</span>
            </Link>
        </div>

        <div class="w-full max-w-md rounded-2xl border border-edge-soft bg-surface p-8 shadow-lg">
            <slot />
        </div>

        <p class="mt-6 text-center text-xs text-content-faint">
            منصة إدارة حلقات تحفيظ القرآن الكريم
        </p>
    </div>
</template>
