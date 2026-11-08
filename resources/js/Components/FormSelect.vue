<script setup lang="ts">
interface Option {
    value: string | number;
    label: string;
}

withDefaults(
    defineProps<{
        modelValue: string | number | null;
        label?: string;
        options: Option[];
        error?: string;
        required?: boolean;
        placeholder?: string;
        id?: string;
        disabled?: boolean;
    }>(),
    {
        label: '',
        error: '',
        required: false,
        placeholder: 'اختر...',
        id: undefined,
        disabled: false,
    },
);

defineEmits<{
    'update:modelValue': [value: string];
}>();
</script>

<template>
    <div>
        <label v-if="label" :for="id" class="mb-1.5 block text-sm font-medium text-content-body">
            {{ label }}
            <span v-if="required" aria-hidden="true" class="text-red-600 dark:text-red-400">*</span>
            <span v-if="required" class="sr-only">(مطلوب)</span>
        </label>
        <select
            :id="id"
            :value="modelValue ?? ''"
            :required="required"
            :disabled="disabled"
            :aria-invalid="error ? 'true' : undefined"
            :aria-describedby="error && id ? `${id}-error` : undefined"
            class="field min-h-11"
            :class="{ 'field-error': error, 'cursor-not-allowed bg-surface-sunken text-content-muted': disabled }"
            @change="$emit('update:modelValue', ($event.target as HTMLSelectElement).value)"
        >
            <option value="" disabled>{{ placeholder }}</option>
            <option v-for="option in options" :key="option.value" :value="option.value">
                {{ option.label }}
            </option>
        </select>
        <p v-if="error" :id="id ? `${id}-error` : undefined" class="mt-1.5 text-sm text-red-600 dark:text-red-400">
            {{ error }}
        </p>
    </div>
</template>
