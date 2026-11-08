<script setup lang="ts">
import Checkbox from '@/Components/Checkbox.vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import AppButton from '@/Components/AppButton.vue';
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
            <h1 class="text-2xl font-bold tabular-nums text-content-strong">تسجيل الدخول</h1>
            <p class="mt-1 text-sm text-content-muted">أهلاً بعودتك إلى منصة تحفيظ</p>
        </div>

        <div
            v-if="status"
            role="status"
            class="mb-4 rounded-lg bg-green-50 px-4 py-3 text-sm font-medium text-green-800 dark:bg-green-500/10 dark:text-green-200"
        >
            {{ status }}
        </div>

        <form class="space-y-5" @submit.prevent="submit">
            <div>
                <label for="email" class="mb-1.5 block text-sm font-medium text-content-body">
                    البريد الإلكتروني
                </label>
                <input
                    id="email"
                    v-model="form.email"
                    type="email"
                    required
                    autofocus
                    autocomplete="username"
                    inputmode="email"
                    placeholder="you@example.com"
                    class="field min-h-11"
                    aria-describedby="email-error"
                />
                <InputError id="email-error" class="mt-1.5" :message="form.errors.email" />
            </div>

            <div>
                <label for="password" class="mb-1.5 block text-sm font-medium text-content-body">
                    كلمة المرور
                </label>
                <input
                    id="password"
                    v-model="form.password"
                    type="password"
                    required
                    autocomplete="current-password"
                    placeholder="••••••••"
                    class="field min-h-11"
                    aria-describedby="password-error"
                />
                <InputError id="password-error" class="mt-1.5" :message="form.errors.password" />
            </div>

            <div class="flex flex-wrap items-center justify-between gap-3">
                <label class="flex min-h-11 items-center gap-2">
                    <Checkbox name="remember" v-model:checked="form.remember" />
                    <span class="text-sm text-content-soft">تذكرني</span>
                </label>

                <Link
                    v-if="canResetPassword"
                    :href="route('password.request')"
                    class="focus-ring rounded-sm text-sm font-medium text-teal-700 underline-offset-4 hover:underline dark:text-teal-300"
                >
                    نسيت كلمة المرور؟
                </Link>
            </div>

            <AppButton type="submit" size="lg" block :loading="form.processing">
                تسجيل الدخول
            </AppButton>
        </form>
    </GuestLayout>
</template>
