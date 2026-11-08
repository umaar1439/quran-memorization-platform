<script setup lang="ts">
import GuestLayout from '@/Layouts/GuestLayout.vue';
import FormInput from '@/Components/FormInput.vue';
import AppButton from '@/Components/AppButton.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('register'), {
        onFinish: () => {
            form.reset('password', 'password_confirmation');
        },
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="إنشاء حساب" />

        <div class="mb-6 text-center">
            <h1 class="text-2xl font-bold tabular-nums text-content-strong">إنشاء حساب</h1>
            <p class="mt-1 text-sm text-content-muted">سجّل بياناتك للانضمام إلى المنصة</p>
        </div>

        <form class="space-y-4" @submit.prevent="submit">
            <FormInput
                id="name"
                v-model="form.name"
                label="الاسم"
                autocomplete="name"
                required
                :error="form.errors.name"
            />

            <FormInput
                id="email"
                v-model="form.email"
                type="email"
                label="البريد الإلكتروني"
                autocomplete="username"
                required
                :error="form.errors.email"
            />

            <FormInput
                id="password"
                v-model="form.password"
                type="password"
                label="كلمة المرور"
                autocomplete="new-password"
                required
                :error="form.errors.password"
            />

            <FormInput
                id="password_confirmation"
                v-model="form.password_confirmation"
                type="password"
                label="تأكيد كلمة المرور"
                autocomplete="new-password"
                required
                :error="form.errors.password_confirmation"
            />

            <AppButton type="submit" size="lg" block :loading="form.processing">
                إنشاء الحساب
            </AppButton>

            <p class="text-center text-sm text-content-muted">
                لديك حساب بالفعل؟
                <Link
                    :href="route('login')"
                    class="link"
                >
                    تسجيل الدخول
                </Link>
            </p>
        </form>
    </GuestLayout>
</template>
