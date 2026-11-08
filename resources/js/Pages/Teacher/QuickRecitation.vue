<script setup lang="ts">
import { onMounted, reactive, ref, watch } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import FormSelect from '@/Components/FormSelect.vue';
import FormInput from '@/Components/FormInput.vue';
import AppButton from '@/Components/AppButton.vue';
import PageHeader from '@/Components/PageHeader.vue';
import QuranRangeSelector, { type QuranRange } from '@/Components/QuranRangeSelector.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import EmptyState from '@/Components/EmptyState.vue';
import OfflineIndicator from '@/Components/OfflineIndicator.vue';
import { useToast } from '@/Composables/useToast';
import { useApi } from '@/Composables/useApi';
import { useNetworkStatus } from '@/Composables/useNetworkStatus';
import { OfflineQueue, generateIdempotencyKey } from '@/Services/OfflineQueue';
import { SyncService } from '@/Services/SyncService';
import type { Recitation, StudentProfile } from '@/Types';

const props = defineProps<{
    students: (StudentProfile & { user?: { name: string } })[];
    recentRecitations: Recitation[];
}>();

const toast = useToast();
const api = useApi();
const { isOnline } = useNetworkStatus();

const typeOptions = [
    { value: 'new', label: 'حفظ جديد' },
    { value: 'near_revision', label: 'مراجعة قريبة' },
    { value: 'far_revision', label: 'مراجعة بعيدة' },
];

const ratingOptions = [
    { value: 'excellent', label: 'ممتاز' },
    { value: 'very_good', label: 'جيد جداً' },
    { value: 'good', label: 'جيد' },
    { value: 'needs_work', label: 'يحتاج إلى تحسين' },
];

const ratingLabels: Record<string, string> = {
    excellent: 'ممتاز',
    very_good: 'جيد جداً',
    good: 'جيد',
    needs_work: 'يحتاج إلى تحسين',
};

const NOTE_OTHER = 'اخرى';

const notesOptions = [
    { value: 'حافظ', label: '1: حافظ' },
    { value: 'حافظ ولديه اخطاء', label: '2: حافظ ولديه اخطاء' },
    { value: 'مخارج الحروف غير سليمه', label: '3: مخارج الحروف غير سليمه' },
    { value: 'غير حافظ', label: '4: غير حافظ' },
    { value: 'لم يحفظ الدرس', label: '5: لم يحفظ الدرس' },
    { value: NOTE_OTHER, label: '6: اخرى' },
];

const studentOptions = props.students.map((s) => ({ value: s.id, label: s.user?.name ?? `طالب #${s.id}` }));

const processing = ref(false);
const errors = reactive<Record<string, string>>({});
const pendingCount = ref(0);
const localRecitations = ref<Recitation[]>([...props.recentRecitations]);

const form = reactive({
    student_id: '' as string | number,
    type: 'new',
    rating: '',
    notesOption: '',
    notesCustom: '',
});

const quranRange = reactive<QuranRange>({
    surahNumber: null,
    ayahFrom: null,
    ayahTo: null,
});

// v-model على كائن reactive معرَّف بـ const يفشل (إعادة تعيين const).
// نستخدم Object.assign لتعديل خصائص الكائن الحالي بدل استبداله كاملاً.
function updateQuranRange(value: QuranRange): void {
    Object.assign(quranRange, value);
}

function clearErrors(): void {
    Object.keys(errors).forEach((key) => delete errors[key]);
}

function resetForm(): void {
    form.student_id = '';
    form.type = 'new';
    form.rating = '';
    form.notesOption = '';
    form.notesCustom = '';
    quranRange.surahNumber = null;
    quranRange.ayahFrom = null;
    quranRange.ayahTo = null;
    clearErrors();
}

async function refreshPendingCount(): Promise<void> {
    const pending = await OfflineQueue.getPending();
    pendingCount.value = pending.length;
}

async function submit(): Promise<void> {
    clearErrors();

    if (!form.student_id || !form.type || !quranRange.surahNumber || !quranRange.ayahFrom || !quranRange.ayahTo || !form.rating || !form.notesOption) {
        toast.error('يرجى إكمال جميع الحقول المطلوبة.');
        return;
    }

    // الملاحظة: نص جاهز من القائمة، أو نص مخصص عند اختيار "اخرى".
    const notes = form.notesOption === NOTE_OTHER
        ? form.notesCustom.trim()
        : form.notesOption;

    if (!notes) {
        toast.error('يرجى كتابة الملاحظة المخصصة.');
        return;
    }

    const payload = {
        student_id: form.student_id,
        type: form.type,
        surah_number: quranRange.surahNumber,
        ayah_from: quranRange.ayahFrom,
        ayah_to: quranRange.ayahTo,
        rating: form.rating,
        notes,
        idempotency_key: generateIdempotencyKey(),
    };

    if (!isOnline.value) {
        await OfflineQueue.enqueue('recitations.create', payload);
        await refreshPendingCount();
        toast.info('تم حفظ التسميع محلياً — سيُرفع تلقائياً عند عودة الاتصال.');
        resetForm();
        return;
    }

    processing.value = true;

    try {
        const response = await api.post<{ data: Recitation }>(route('recitations.store'), payload);
        localRecitations.value.unshift(response.data.data);
        toast.success('تم تسجيل التسميع بنجاح.');
        resetForm();
    } catch (error) {
        const validationErrors = api.extractValidationErrors(error);
        Object.entries(validationErrors).forEach(([key, messages]) => (errors[key] = messages[0]));

        if (Object.keys(validationErrors).length === 0) {
            // فشل الشبكة أثناء الإرسال (مثلاً انقطاع مفاجئ) → تراجع إلى الطابور المحلي.
            await OfflineQueue.enqueue('recitations.create', payload);
            await refreshPendingCount();
            toast.warning('تعذّر الاتصال بالخادم — تم حفظ التسميع محلياً.');
            resetForm();
        }
    } finally {
        processing.value = false;
    }
}

async function trySync(): Promise<void> {
    if (!isOnline.value) return;

    const pending = await OfflineQueue.getPending();
    if (pending.length === 0) return;

    const results = await SyncService.syncAll();
    await refreshPendingCount();

    const succeeded = results.filter((r) => r.outcome === 'success' || r.outcome === 'duplicate').length;
    if (succeeded > 0) {
        toast.success(`تمت مزامنة ${succeeded} تسميع(ات) معلّقة بنجاح.`);
    }
}

watch(isOnline, (online) => {
    if (online) {
        trySync();
    }
});

onMounted(async () => {
    await refreshPendingCount();
    await trySync();
});
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
