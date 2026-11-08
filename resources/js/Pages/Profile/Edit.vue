<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import UpdatePasswordForm from './Partials/UpdatePasswordForm.vue';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm.vue';
import PageHeader from '@/Components/PageHeader.vue';
import { Head } from '@inertiajs/vue3';

defineProps<{
    mustVerifyEmail?: boolean;
    status?: string;
}>();
</script>

<template>
    <Head title="الملف الشخصي" />

    <AuthenticatedLayout>
        <template #header>
            <PageHeader
                title="الملف الشخصي"
                description="حدّث بياناتك الشخصية وكلمة المرور."
            />
        </template>

        <div class="mx-auto max-w-3xl space-y-6">
            <div class="card p-4 sm:p-8">
                <UpdateProfileInformationForm
                    :must-verify-email="mustVerifyEmail"
                    :status="status"
                />
            </div>

            <div class="card p-4 sm:p-8">
                <UpdatePasswordForm />
            </div>
        </div>
    </AuthenticatedLayout>
</template>
