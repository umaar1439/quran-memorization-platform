import { onMounted, reactive, ref, watch } from 'vue';
import type { QuranRange } from '@/Components/QuranRangeSelector.vue';
import { useApi } from '@/Composables/useApi';
import { useNetworkStatus } from '@/Composables/useNetworkStatus';
import { useToast } from '@/Composables/useToast';
import { OfflineQueue, generateIdempotencyKey } from '@/Services/OfflineQueue';
import { RecitationApi } from '@/Services/RecitationApi';
import { SyncService } from '@/Services/SyncService';
import type {
    RecitationCreatePayload,
    RecitationListItem,
    RecitationRatingValue,
    RecitationTypeValue,
} from '@/Types/api/recitation';
import type { Recitation, StudentProfile } from '@/Types';

/**
 * ViewModel لصفحة "تسميع سريع": يملك حالة النموذج، الأخطاء، حالة الإرسال،
 * طابور العمل دون اتصال ومزامنته. المكوّن يعرض القيم ويستدعي submit فقط،
 * ولا يعرف شيئاً عن axios أو مسار الـ endpoint أو مفاتيح الـ payload.
 */

const NOTE_OTHER = 'اخرى';

const typeOptions: { value: RecitationTypeValue; label: string }[] = [
    { value: 'new', label: 'حفظ جديد' },
    { value: 'near_revision', label: 'مراجعة قريبة' },
    { value: 'far_revision', label: 'مراجعة بعيدة' },
];

const ratingOptions: { value: RecitationRatingValue; label: string }[] = [
    { value: 'excellent', label: 'ممتاز' },
    { value: 'very_good', label: 'جيد جداً' },
    { value: 'good', label: 'جيد' },
    { value: 'needs_work', label: 'يحتاج إلى تحسين' },
];

const ratingLabels: Record<string, string> = Object.fromEntries(
    ratingOptions.map((option) => [option.value, option.label]),
);

const notesOptions = [
    { value: 'حافظ', label: '1: حافظ' },
    { value: 'حافظ ولديه اخطاء', label: '2: حافظ ولديه اخطاء' },
    { value: 'مخارج الحروف غير سليمه', label: '3: مخارج الحروف غير سليمه' },
    { value: 'غير حافظ', label: '4: غير حافظ' },
    { value: 'لم يحفظ الدرس', label: '5: لم يحفظ الدرس' },
    { value: NOTE_OTHER, label: '6: اخرى' },
];

/** يحوّل قيمة عنصر select نصية إلى نوع مدقّق قبل إرسالها للخادم. */
function isRecitationType(value: string): value is RecitationTypeValue {
    return typeOptions.some((option) => option.value === value);
}

function isRecitationRating(value: string): value is RecitationRatingValue {
    return ratingOptions.some((option) => option.value === value);
}


export function useQuickRecitation(
    students: readonly (StudentProfile & { user?: { name: string } })[],
    recentRecitations: readonly Recitation[],
) {
    const toast = useToast();
    const api = useApi();
    const { isOnline } = useNetworkStatus();

    const studentOptions = students.map((student) => ({
        value: student.id,
        label: student.user?.name ?? `طالب #${student.id}`,
    }));

    const processing = ref(false);
    const errors = reactive<Record<string, string>>({});
    const pendingCount = ref(0);
    const localRecitations = ref<RecitationListItem[]>([...recentRecitations]);

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

        const { student_id, type, rating, notesOption } = form;
        const surahNumber = quranRange.surahNumber;
        const ayahFrom = quranRange.ayahFrom;
        const ayahTo = quranRange.ayahTo;

        if (
            !student_id ||
            !surahNumber ||
            !ayahFrom ||
            !ayahTo ||
            !notesOption ||
            !isRecitationType(type) ||
            !isRecitationRating(rating)
        ) {
            toast.error('يرجى إكمال جميع الحقول المطلوبة.');
            return;
        }

        // الملاحظة: نص جاهز من القائمة، أو نص مخصص عند اختيار "اخرى".
        const notes = notesOption === NOTE_OTHER ? form.notesCustom.trim() : notesOption;

        if (!notes) {
            toast.error('يرجى كتابة الملاحظة المخصصة.');
            return;
        }

        // المفاتيح نفسها التي يرسلها SyncService من الطابور المحلي — لا تُغيَّر.
        const payload: RecitationCreatePayload = {
            student_id,
            type,
            surah_number: surahNumber,
            ayah_from: ayahFrom,
            ayah_to: ayahTo,
            rating,
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
            const recitation = await RecitationApi.create(payload);
            localRecitations.value.unshift(recitation);
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

    return {
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
    };
}

