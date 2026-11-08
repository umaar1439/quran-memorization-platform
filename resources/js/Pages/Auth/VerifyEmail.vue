<script setup lang="ts">
import { computed } from 'vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import AppButton from '@/Components/AppButton.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps<{
    status?: string;
}>();

const form = useForm({});

const submit = () => {
    form.post(route('verification.send'));
};

const verificationLinkSent = computed(
    () => props.status === 'verification-link-sent',
);
</script>

<template>
    <GuestLayout>
        <Head title="تأكيد البريد الإلكتروني" />

        <div class="mb-6 text-center">
            <h1 class="text-2xl font-bold text-content-strong">تأكيد البريد الإلكتروني</h1>
            <p class="mt-1 text-sm text-content-muted">
                أرسلنا رابط تحقق إلى بريدك. اضغط عليه لتفعيل حسابك، أو اطلب رابطاً جديداً.
            </p>
        </div>

        <div
            v-if="verificationLinkSent"
            role="status"
            class="mb-4 rounded-lg bg-green-50 px-4 py-3 text-sm font-medium text-green-800 dark:bg-green-500/10 dark:text-green-200"
        >
            تم إرسال رابط تحقق جديد إلى بريدك الإلكتروني.
        </div>

        <form class="space-y-3" @submit.prevent="submit">
            <AppButton type="submit" size="lg" block :loading="form.processing">
                إعادة إرسال رابط التحقق
            </AppButton>

            <Link
                :href="route('logout')"
                method="post"
                as="button"
                class="focus-ring block w-full rounded-lg px-4 py-2.5 text-center text-sm font-medium text-content-muted hover:text-content-body"
            >
                تسجيل الخروج
            </Link>
        </form>
    </GuestLayout>
</template>
