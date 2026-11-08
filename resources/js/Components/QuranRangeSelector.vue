<script setup lang="ts">
import { computed } from 'vue';
import FormSelect from '@/Components/FormSelect.vue';
import { SURAHS } from '@/Data/surahs';

export interface QuranRange {
    surahNumber: number | null;
    ayahFrom: number | null;
    ayahTo: number | null;
}

const props = defineProps<{
    modelValue: QuranRange;
}>();

const emit = defineEmits<{
    'update:modelValue': [value: QuranRange];
}>();

const surahOptions = SURAHS.map((surah) => ({
    value: surah.number,
    label: `${surah.number}. ${surah.nameArabic}`,
}));

const selectedSurah = computed(() =>
    SURAHS.find((surah) => surah.number === props.modelValue.surahNumber),
);

// أرقام الآيات المتاحة للسورة المختارة (1..ayahCount).
const ayahOptions = computed(() => {
    if (!selectedSurah.value) return [];

    return Array.from({ length: selectedSurah.value.ayahCount }, (_, index) => ({
        value: index + 1,
        label: `الآية ${index + 1}`,
    }));
});

// قائمة "إلى" تقتصر على الآيات >= "من" لضمان نطاق سليم دائماً.
const ayahToOptions = computed(() => {
    const from = props.modelValue.ayahFrom;
    if (from === null) return ayahOptions.value;

    return ayahOptions.value.filter((option) => option.value >= from);
});

function onSurahChange(value: string): void {
    // عند تغيير السورة: تصفير نطاق الآيات لأن حدودها تغيّرت.
    emit('update:modelValue', {
        surahNumber: value === '' ? null : Number(value),
        ayahFrom: null,
        ayahTo: null,
    });
}

function onAyahFromChange(value: string): void {
    const ayahFrom = value === '' ? null : Number(value);

    // إذا كانت آية النهاية السابقة قبل البداية الجديدة، صفّرها لإجبار إعادة الاختيار.
    const ayahTo = props.modelValue.ayahTo !== null
        && ayahFrom !== null
        && props.modelValue.ayahTo >= ayahFrom
        ? props.modelValue.ayahTo
        : null;

    emit('update:modelValue', {
        ...props.modelValue,
        ayahFrom,
        ayahTo,
    });
}

function onAyahToChange(value: string): void {
    emit('update:modelValue', {
        ...props.modelValue,
        ayahTo: value === '' ? null : Number(value),
    });
}
</script>

<template>
    <div class="space-y-3">
        <FormSelect
            id="surah-number"
            label="السورة"
            :model-value="modelValue.surahNumber"
            :options="surahOptions"
            required
            @update:model-value="onSurahChange($event)"
        />
        <div class="grid grid-cols-2 gap-3">
            <FormSelect
                id="ayah-from"
                label="من آية"
                :model-value="modelValue.ayahFrom"
                :options="ayahOptions"
                :disabled="!selectedSurah"
                required
                @update:model-value="onAyahFromChange($event)"
            />
            <FormSelect
                id="ayah-to"
                label="إلى آية"
                :model-value="modelValue.ayahTo"
                :options="ayahToOptions"
                :disabled="modelValue.ayahFrom === null"
                required
                @update:model-value="onAyahToChange($event)"
            />
        </div>
        <p v-if="selectedSurah" class="text-xs text-content-muted">
            عدد آيات {{ selectedSurah.nameArabic }}: {{ selectedSurah.ayahCount }}
        </p>
        <p v-else class="text-xs text-content-muted">
            اختر السورة أولاً لعرض أرقام الآيات المتاحة.
        </p>
    </div>
</template>
