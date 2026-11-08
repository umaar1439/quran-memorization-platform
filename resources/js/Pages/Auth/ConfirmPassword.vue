<script setup lang="ts">
import GuestLayout from '@/Layouts/GuestLayout.vue';
import FormInput from '@/Components/FormInput.vue';
import AppButton from '@/Components/AppButton.vue';
import { Head, useForm } from '@inertiajs/vue3';

const form = useForm({
    password: '',
});

const submit = () => {
    form.post(route('password.confirm'), {
        onFinish: () => {
            form.reset();
        },
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="تأكيد كلمة المرور" />

        <div class="mb-6 text-center">
            <h1 class="text-2xl font-bold text-content-strong">تأكيد كلمة المرور</h1>
            <p class="mt-1 text-sm text-content-muted">
                هذه منطقة آمنة؛ أكّد كلمة المرور قبل المتابعة.
            </p>
        </div>

        <form class="space-y-4" @submit.prevent="submit">
            <FormInput
                id="password"
                v-model="form.password"
                type="password"
                label="كلمة المرور"
                autocomplete="current-password"
                required
                :error="form.errors.password"
            />

            <AppButton type="submit" size="lg" block :loading="form.processing">
                تأكيد
            </AppButton>
        </form>
    </GuestLayout>
</template>
