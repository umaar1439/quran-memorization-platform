<script setup lang="ts">
import { computed } from 'vue';
import FormSelect from '@/Components/FormSelect.vue';
import FormInput from '@/Components/FormInput.vue';
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

const ayahToError = computed(() => {
    if (!selectedSurah.value || !props.modelValue.ayahTo) {
        return '';
    }

    if (props.modelValue.ayahTo > selectedSurah.value.ayahCount) {
        return `أقصى عدد آيات في هذه السورة هو ${selectedSurah.value.ayahCount}`;
    }

    if (props.modelValue.ayahFrom && props.modelValue.ayahTo < props.modelValue.ayahFrom) {
        return 'يجب أن تكون آية النهاية بعد آية البداية';
    }

    return '';
});

function updateField(field: keyof QuranRange, value: string): void {
    emit('update:modelValue', {
        ...props.modelValue,
        [field]: value === '' ? null : Number(value),
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
            @update:model-value="updateField('surahNumber', $event)"
        />
        <div class="grid grid-cols-2 gap-3">
            <FormInput
                id="ayah-from"
                type="number"
                label="من آية"
                :model-value="modelValue.ayahFrom"
                required
                @update:model-value="updateField('ayahFrom', $event)"
            />
            <FormInput
                id="ayah-to"
                type="number"
                label="إلى آية"
                :model-value="modelValue.ayahTo"
                :error="ayahToError"
                required
                @update:model-value="updateField('ayahTo', $event)"
            />
        </div>
        <p v-if="selectedSurah" class="text-xs text-gray-500">
            عدد آيات {{ selectedSurah.nameArabic }}: {{ selectedSurah.ayahCount }}
        </p>
    </div>
</template>
