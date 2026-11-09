<script setup lang="ts">
import { computed } from 'vue';
import type { FormErrors, SelectOption } from '@/Types/api/common';
import type { AttendanceCircleOption, AttendanceStatusValue } from '@/Types/api/attendance';
import type { AttendanceSheetViewRow } from '@/Composables/useAttendanceSheet';

/**
 * عرض فقط لورقة الحضور اليومية: يستقبل الصفوف المدمجة بالحالة المحلية،
 * ويعرض شريط التاريخ/الحلقة، الملخص، وأزرار الحالة لكل طالب.
 * لا منطق عمل ولا نداءات شبكة هنا — كل تفاعل يُطلق حدثاً إلى الـ ViewModel.
 */
const props = defineProps<{
    rows: AttendanceSheetViewRow[];
    statusOptions: { value: AttendanceStatusValue; label: string }[];
    summaryLabels: SelectOption[];
    summary: Record<string, number>;
    circles: AttendanceCircleOption[];
    circleId: number | null;
    date: string;
    markedCount: number;
    canManage: boolean;
    processing: boolean;
    errors?: FormErrors;
}>();

const emit = defineEmits<{
    'set-status': [studentId: number, status: AttendanceStatusValue];
    'set-note': [studentId: number, value: string];
    'mark-all': [];
    'clear-all': [];
    'change-date': [event: Event];
    'change-circle': [event: Event];
    save: [];
}>();

/** لا يمكن تسجيل حضور تاريخ مستقبلي — نفس قيد BulkAttendanceRequest. */
const today = new Date().toISOString().slice(0, 10);

const activeClasses: Record<AttendanceStatusValue, string> = {
    present: 'border-green-600 bg-green-600 text-white',
    absent: 'border-red-600 bg-red-600 text-white',
    late: 'border-amber-500 bg-amber-500 text-white',
    excused: 'border-edge-strong bg-surface-sunken text-content-strong',
};

const canSave = computed(() => props.canManage && !props.processing && props.markedCount > 0);
</script>

<template>
    <section class="space-y-4">
        <div class="flex flex-wrap items-end gap-3">
            <div>
                <label for="attendance-date" class="mb-1.5 block text-sm font-medium text-content-body">
                    التاريخ
                </label>
                <input
                    id="attendance-date"
                    type="date"
                    :value="date"
                    :max="today"
                    class="field min-h-11"
                    @change="emit('change-date', $event)"
                />
            </div>

            <div v-if="circles.length > 1">
                <label for="attendance-circle" class="mb-1.5 block text-sm font-medium text-content-body">
                    الحلقة
                </label>
                <select
                    id="attendance-circle"
                    :value="circleId ?? ''"
                    class="field min-h-11"
                    @change="emit('change-circle', $event)"
                >
                    <option v-for="circle in circles" :key="circle.id" :value="circle.id">
                        {{ circle.name }}
                    </option>
                </select>
            </div>

            <p v-else-if="circles.length === 1" class="pb-2.5 text-sm text-content-muted">
                {{ circles[0]?.name }}
            </p>
        </div>

        <ul class="flex flex-wrap gap-2" aria-label="ملخص حضور اليوم">
            <li
                v-for="entry in summaryLabels"
                :key="entry.value"
                class="rounded-full bg-surface-sunken px-3 py-1 text-xs font-medium text-content-body"
            >
                {{ entry.label }}: {{ summary[entry.value] ?? 0 }}
            </li>
        </ul>

        <div v-if="canManage" class="flex flex-wrap gap-2">
            <button
                type="button"
                class="focus-ring inline-flex min-h-11 items-center justify-center rounded-lg btn-secondary px-4 text-sm font-medium transition duration-150 ease-standard"
                :disabled="processing || rows.length === 0"
                @click="emit('mark-all')"
            >
                تحديد الكل حاضر
            </button>
            <button
                type="button"
                class="focus-ring inline-flex min-h-11 items-center justify-center rounded-lg btn-secondary px-4 text-sm font-medium transition duration-150 ease-standard"
                :disabled="processing || rows.length === 0"
                @click="emit('clear-all')"
            >
                مسح التحديد
            </button>
        </div>

        <p v-if="errors?.records" class="text-sm text-red-600 dark:text-red-400">
            {{ errors.records }}
        </p>
        <p v-if="errors?.circle_id" class="text-sm text-red-600 dark:text-red-400">
            {{ errors.circle_id }}
        </p>
        <p v-if="errors?.date" class="text-sm text-red-600 dark:text-red-400">
            {{ errors.date }}
        </p>

        <p
            v-if="rows.length === 0"
            class="rounded-xl border border-edge-soft p-6 text-center text-sm text-content-muted"
        >
            لا يوجد طلاب نشطون في هذه الحلقة.
        </p>

        <ul v-else class="space-y-2">
            <li
                v-for="row in rows"
                :key="row.student_id"
                class="rounded-xl border border-edge bg-surface p-3"
            >
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div class="min-w-0">
                        <p class="truncate font-medium text-content-strong">{{ row.student_name }}</p>
                        <p class="text-xs text-content-muted">#{{ row.enrollment_number }}</p>
                    </div>

                    <div
                        class="flex flex-wrap gap-2"
                        role="radiogroup"
                        :aria-label="`حالة ${row.student_name}`"
                    >
                        <button
                            v-for="option in statusOptions"
                            :key="option.value"
                            type="button"
                            role="radio"
                            :aria-checked="row.status === option.value"
                            :disabled="!canManage || processing"
                            class="focus-ring inline-flex min-h-11 min-w-20 items-center justify-center rounded-lg border px-3 text-sm font-medium transition duration-150 ease-standard"
                            :class="
                                row.status === option.value
                                    ? activeClasses[option.value]
                                    : 'border-edge bg-surface text-content-body hover:bg-surface-sunken'
                            "
                            @click="emit('set-status', row.student_id, option.value)"
                        >
                            {{ option.label }}
                        </button>
                    </div>
                </div>

                <input
                    type="text"
                    :value="row.notes"
                    :disabled="!canManage || processing"
                    :aria-label="`ملاحظة على ${row.student_name}`"
                    class="field mt-3 min-h-11 w-full"
                    placeholder="ملاحظة (اختياري)"
                    @input="emit('set-note', row.student_id, ($event.target as HTMLInputElement).value)"
                />
            </li>
        </ul>

        <div v-if="canManage" class="flex justify-end">
            <button
                type="button"
                :disabled="!canSave"
                class="focus-ring inline-flex min-h-11 items-center justify-center rounded-lg btn-primary px-5 text-sm font-semibold transition duration-150 ease-standard"
                @click="emit('save')"
            >
                {{ processing ? 'جارٍ الحفظ…' : `حفظ الحضور (${markedCount})` }}
            </button>
        </div>
    </section>
</template>
