<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import FormSelect from '@/Components/FormSelect.vue';
import FormInput from '@/Components/FormInput.vue';
import AppButton from '@/Components/AppButton.vue';
import PageHeader from '@/Components/PageHeader.vue';
import QuranRangeSelector from '@/Components/QuranRangeSelector.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import EmptyState from '@/Components/EmptyState.vue';
import OfflineIndicator from '@/Components/OfflineIndicator.vue';
import { useQuickRecitation } from '@/Composables/useQuickRecitation';
import type { Recitation, StudentProfile } from '@/Types';

/**
 * View: عرض القيم والتقاط أحداث المستخدم فقط. الحالة والإرسال والطابور
 * المحلي والمزامنة كلها في useQuickRecitation (ViewModel).
 */
const props = defineProps<{
    students: (StudentProfile & { user?: { name: string } })[];
    recentRecitations: Recitation[];
}>();

const {
    form,
    quranRange,
    updateQuranRange,
    errors,
    processing,
    pendingCount,
    localRecitations,
    studentOptions,
    typeOptions,
    ratingOptions,
    notesOptions,
    NOTE_OTHER,
    ratingLabels,
    isOnline,
    submit,
} = useQuickRecitation(props.students, props.recentRecitations);
</script>

<template>
    <Head title="تسميع سريع" />

    <AuthenticatedLayout>
        <template #header>
            <PageHeader title="تسميع سريع" description="سجّل تسميع الطالب خلال ثوانٍ، ويعمل دون اتصال.">
                <template #actions>
                    <span
                        v-if="pendingCount > 0"
                        class="rounded-full bg-amber-100 px-3 py-1 text-xs font-medium text-amber-800 dark:bg-amber-500/15 dark:text-amber-200"
                    >
                        {{ pendingCount }} تسميع معلّق محلياً
                    </span>
                    <OfflineIndicator />
                </template>
            </PageHeader>
        </template>

        <div class="mx-auto grid max-w-5xl grid-cols-1 gap-6 lg:grid-cols-2">
            <div class="card p-6">
                <h3 class="mb-4 text-base font-semibold text-content">تسجيل تسميع جديد</h3>
                <form class="space-y-4" @submit.prevent="submit">
                    <FormSelect id="recitation-student" v-model="form.student_id" label="الطالب" :options="studentOptions" required :error="errors.student_id" />
                    <FormSelect id="recitation-type" v-model="form.type" label="نوع التسميع" :options="typeOptions" required :error="errors.type" />
                    <QuranRangeSelector :model-value="quranRange" @update:model-value="updateQuranRange" />
                    <FormSelect id="recitation-rating" v-model="form.rating" label="التقييم" :options="ratingOptions" :error="errors.rating" required placeholder="اختر التقييم" />
                    <FormSelect id="recitation-notes" v-model="form.notesOption" label="الملاحظات" :options="notesOptions" :error="errors.notes" required placeholder="اختر الملاحظة" />
                    <FormInput
                        v-if="form.notesOption === NOTE_OTHER"
                        id="recitation-notes-custom"
                        v-model="form.notesCustom"
                        label="اكتب ملاحظتك الخاصة"
                        :error="errors.notes"
                        required
                        placeholder="اكتب الملاحظة المخصصة..."
                    />

                    <AppButton type="submit" size="lg" block :loading="processing">
                        {{ isOnline ? 'تسجيل التسميع' : 'حفظ محلياً (بدون اتصال)' }}
                    </AppButton>
                </form>
            </div>

            <div class="card">
                <div class="border-b border-edge px-4 py-3">
                    <h3 class="text-base font-semibold text-content">آخر التسميعات</h3>
                </div>
                <div v-if="localRecitations.length === 0" class="p-4">
                    <EmptyState title="لا توجد تسميعات مسجَّلة بعد" />
                </div>
                <ul v-else class="max-h-[520px] divide-y divide-edge-soft overflow-y-auto">
                    <li v-for="recitation in localRecitations" :key="recitation.id" class="flex flex-wrap items-center justify-between gap-x-3 gap-y-1 px-4 py-3 text-sm">
                        <div>
                            <p class="font-medium text-content-strong">{{ recitation.student?.user?.name ?? 'طالب' }}</p>
                            <p class="text-content-muted">
                                سورة {{ recitation.surah_number }} — الآيات {{ recitation.ayah_from }} إلى {{ recitation.ayah_to }}
                            </p>
                        </div>
                        <StatusBadge v-if="recitation.rating" :status="recitation.rating" :labels="ratingLabels" />
                    </li>
                </ul>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
