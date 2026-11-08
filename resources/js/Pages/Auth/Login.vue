<script setup lang="ts">
import Checkbox from '@/Components/Checkbox.vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps<{
    canResetPassword?: boolean;
    status?: string;
}>();

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => {
            form.reset('password');
        },
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="تسجيل الدخول" />

        <div class="mb-6 text-center">
            <h1 class="text-2xl font-bold text-content">تسجيل الدخول</h1>
            <p class="mt-1 text-sm text-content-muted">أهلاً بعودتك إلى منصة تحفيظ</p>
        </div>

        <div v-if="status" class="mb-4 rounded-lg bg-green-50 dark:bg-green-500/10 px-4 py-3 text-sm font-medium text-green-700 dark:text-green-300">
            {{ status }}
        </div>

        <form @submit.prevent="submit" class="space-y-5">
            <div>
                <label for="email" class="mb-1.5 block text-sm font-medium text-content-body">البريد الإلكتروني</label>
                <input
                    id="email"
                    type="email"
                    v-model="form.email"
                    required
                    autofocus
                    autocomplete="username"
                    placeholder="you@example.com"
                    class="block w-full rounded-lg border-edge-strong bg-surface text-content shadow-sm focus:border-emerald-500 focus:ring-emerald-500 sm:text-sm"
                />
                <InputError class="mt-2" :message="form.errors.email" />
            </div>

            <div>
                <label for="password" class="mb-1.5 block text-sm font-medium text-content-body">كلمة المرور</label>
                <input
                    id="password"
                    type="password"
                    v-model="form.password"
                    required
                    autocomplete="current-password"
                    placeholder="••••••••"
                    class="block w-full rounded-lg border-edge-strong bg-surface text-content shadow-sm focus:border-emerald-500 focus:ring-emerald-500 sm:text-sm"
                />
                <InputError class="mt-2" :message="form.errors.password" />
            </div>

            <div class="flex items-center justify-between">
                <label class="flex items-center gap-2">
                    <Checkbox name="remember" v-model:checked="form.remember" />
                    <span class="text-sm text-content-soft">تذكرني</span>
                </label>

                <Link
                    v-if="canResetPassword"
                    :href="route('password.request')"
                    class="text-sm font-medium text-emerald-600 dark:text-emerald-400 transition hover:text-emerald-700 dark:hover:text-emerald-300"
                >
                    نسيت كلمة المرور؟
                </Link>
            </div>

            <button
                type="submit"
                :disabled="form.processing"
                class="flex w-full items-center justify-center rounded-lg bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 disabled:opacity-50"
            >
                تسجيل الدخول
            </button>
        </form>
    </GuestLayout>
</template>
