<script setup lang="ts">
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import AppButton from '@/Components/AppButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Link, useForm, usePage } from '@inertiajs/vue3';

defineProps<{
    mustVerifyEmail?: Boolean;
    status?: String;
}>();

const user = usePage().props.auth.user;

const form = useForm({
    name: user.name,
    email: user.email,
});
</script>

<template>
    <section>
        <header>
            <h2 class="text-lg font-semibold text-content-strong">
                بيانات الملف الشخصي
            </h2>

            <p class="mt-1 text-sm text-content-muted">
                حدّث بيانات حسابك الشخصية وعنوان بريدك الإلكتروني.
            </p>
        </header>

        <form
            @submit.prevent="form.patch(route('profile.update'))"
            class="mt-6 space-y-5"
        >
            <div>
                <InputLabel for="name" value="الاسم" />

                <TextInput
                    id="name"
                    type="text"
                    class="mt-1 block w-full"
                    v-model="form.name"
                    required
                    autofocus
                    autocomplete="name"
                />

                <InputError class="mt-2" :message="form.errors.name" />
            </div>

            <div>
                <InputLabel for="email" value="البريد الإلكتروني" />

                <TextInput
                    id="email"
                    type="email"
                    class="mt-1 block w-full"
                    v-model="form.email"
                    required
                    autocomplete="username"
                />

                <InputError class="mt-2" :message="form.errors.email" />
            </div>

            <div v-if="mustVerifyEmail && user.email_verified_at === null">
                <p class="mt-2 text-sm text-content-body">
                    بريدك الإلكتروني غير موثّق.
                    <Link
                        :href="route('verification.send')"
                        method="post"
                        as="button"
                        class="focus-ring rounded-sm text-sm font-medium text-teal-700 underline underline-offset-4 hover:text-teal-800 dark:text-teal-300 dark:hover:text-teal-200"
                    >
                        اضغط هنا لإعادة إرسال بريد التحقق.
                    </Link>
                </p>

                <div
                    v-show="status === 'verification-link-sent'"
                    class="mt-2 text-sm font-medium text-green-700 dark:text-green-300"
                >
                    تم إرسال رابط تحقق جديد إلى بريدك الإلكتروني.
                </div>
            </div>

            <div class="flex items-center gap-4">
                <AppButton :disabled="form.processing">حفظ</AppButton>

                <Transition
                    enter-active-class="transition ease-in-out"
                    enter-from-class="opacity-0"
                    leave-active-class="transition ease-in-out"
                    leave-to-class="opacity-0"
                >
                    <p
                        v-if="form.recentlySuccessful"
                        class="text-sm text-content-muted"
                    >
                        تم الحفظ.
                    </p>
                </Transition>
            </div>
        </form>
    </section>
</template>
