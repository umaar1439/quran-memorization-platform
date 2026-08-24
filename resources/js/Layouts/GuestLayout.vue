<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div class="flex min-h-screen flex-col items-center justify-center bg-gradient-to-b from-emerald-50 via-white to-emerald-50 px-4 py-8">
        <div class="mb-6">
            <Link href="/" class="flex items-center gap-2">
                <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-emerald-600 text-xl font-bold text-white">ت</span>
                <span class="text-xl font-bold text-emerald-800">تحفيظ</span>
            </Link>
        </div>

        <div class="w-full max-w-md rounded-2xl border border-gray-100 bg-white p-8 shadow-lg">
            <slot />
        </div>

        <p class="mt-6 text-center text-xs text-gray-400">
            منصة إدارة حلقات تحفيظ القرآن الكريم
        </p>
    </div>
</template>
