<script setup lang="ts">
import GuestLayout from '@/Layouts/GuestLayout.vue';
import FormInput from '@/Components/FormInput.vue';
import AppButton from '@/Components/AppButton.vue';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps<{
    email: string;
    token: string;
}>();

const form = useForm({
    token: props.token,
    email: props.email,
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('password.store'), {
        onFinish: () => {
            form.reset('password', 'password_confirmation');
        },
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="تعيين كلمة مرور جديدة" />

        <div class="mb-6 text-center">
            <h1 class="text-2xl font-bold text-content-strong">تعيين كلمة مرور جديدة</h1>
            <p class="mt-1 text-sm text-content-muted">اختر كلمة مرور قوية لحسابك.</p>
        </div>

        <form class="space-y-4" @submit.prevent="submit">
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
                label="كلمة المرور الجديدة"
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
                حفظ كلمة المرور
            </AppButton>
        </form>
    </GuestLayout>
</template>
