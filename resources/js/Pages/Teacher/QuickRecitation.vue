<script setup lang="ts">
import { onMounted, reactive, ref, watch } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import FormSelect from '@/Components/FormSelect.vue';
import FormInput from '@/Components/FormInput.vue';
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

const studentOptions = props.students.map((s) => ({ value: s.id, label: s.user?.name ?? `طالب #${s.id}` }));

const processing = ref(false);
const errors = reactive<Record<string, string>>({});
const pendingCount = ref(0);
const localRecitations = ref<Recitation[]>([...props.recentRecitations]);

const form = reactive({
    student_id: '' as string | number,
    type: 'new',
    rating: '',
    notes: '',
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
    form.notes = '';
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

    if (!form.student_id || !quranRange.surahNumber || !quranRange.ayahFrom || !quranRange.ayahTo) {
        toast.error('يرجى إكمال جميع الحقول المطلوبة.');
        return;
    }

    const payload = {
        student_id: form.student_id,
        type: form.type,
        surah_number: quranRange.surahNumber,
        ayah_from: quranRange.ayahFrom,
        ayah_to: quranRange.ayahTo,
        rating: form.rating || null,
        notes: form.notes || null,
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
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">تسميع سريع</h2>
                <div class="flex items-center gap-2">
                    <span v-if="pendingCount > 0" class="text-xs text-amber-700">
                        {{ pendingCount }} تسميع(ات) معلّقة محلياً
                    </span>
                    <OfflineIndicator />
                </div>
            </div>
        </template>

        <div class="mx-auto grid max-w-5xl grid-cols-1 gap-6 lg:grid-cols-2">
            <div class="rounded-lg bg-white p-6 shadow-sm">
                <h3 class="mb-4 text-base font-semibold text-gray-900">تسجيل تسميع جديد</h3>
                <form class="space-y-4" @submit.prevent="submit">
                    <FormSelect id="recitation-student" v-model="form.student_id" label="الطالب" :options="studentOptions" required :error="errors.student_id" />
                    <FormSelect id="recitation-type" v-model="form.type" label="نوع التسميع" :options="typeOptions" required :error="errors.type" />
                    <QuranRangeSelector :model-value="quranRange" @update:model-value="updateQuranRange" />
                    <FormSelect id="recitation-rating" v-model="form.rating" label="التقييم" :options="ratingOptions" :error="errors.rating" placeholder="اختياري" />
                    <FormInput id="recitation-notes" v-model="form.notes" label="ملاحظات" :error="errors.notes" />

                    <button
                        type="submit"
                        :disabled="processing"
                        class="min-h-[44px] w-full rounded-md bg-teal-600 px-4 py-2 text-sm font-medium text-white hover:bg-teal-700 disabled:opacity-50"
                    >
                        {{ isOnline ? 'تسجيل التسميع' : 'حفظ محلياً (بدون اتصال)' }}
                    </button>
                </form>
            </div>

            <div class="rounded-lg bg-white shadow-sm">
                <div class="border-b border-gray-200 px-4 py-3">
                    <h3 class="text-base font-semibold text-gray-900">آخر التسميعات</h3>
                </div>
                <div v-if="localRecitations.length === 0" class="p-4">
                    <EmptyState title="لا توجد تسميعات مسجَّلة بعد" />
                </div>
                <ul v-else class="max-h-[520px] divide-y divide-gray-100 overflow-y-auto">
                    <li v-for="recitation in localRecitations" :key="recitation.id" class="flex items-center justify-between px-4 py-3 text-sm">
                        <div>
                            <p class="font-medium text-gray-800">{{ recitation.student?.user?.name ?? 'طالب' }}</p>
                            <p class="text-gray-500">
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
