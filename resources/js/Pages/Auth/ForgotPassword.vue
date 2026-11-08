<script setup lang="ts">
import GuestLayout from '@/Layouts/GuestLayout.vue';
import FormInput from '@/Components/FormInput.vue';
import AppButton from '@/Components/AppButton.vue';
import { Head, useForm } from '@inertiajs/vue3';

defineProps<{
    status?: string;
}>();

const form = useForm({
    email: '',
});

const submit = () => {
    form.post(route('password.email'));
};
</script>

<template>
    <GuestLayout>
        <Head title="استعادة كلمة المرور" />

        <div class="mb-6 text-center">
            <h1 class="text-2xl font-bold text-content-strong">استعادة كلمة المرور</h1>
            <p class="mt-1 text-sm text-content-muted">
                أدخل بريدك الإلكتروني وسنرسل لك رابطاً لإعادة تعيين كلمة المرور.
            </p>
        </div>

        <div
            v-if="status"
            role="status"
            class="mb-4 rounded-lg bg-green-50 px-4 py-3 text-sm font-medium text-green-800 dark:bg-green-500/10 dark:text-green-200"
        >
            {{ status }}
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

            <AppButton type="submit" size="lg" block :loading="form.processing">
                إرسال رابط إعادة التعيين
            </AppButton>
        </form>
    </GuestLayout>
</template>
