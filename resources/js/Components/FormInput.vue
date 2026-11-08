<script setup lang="ts">
withDefaults(
    defineProps<{
        modelValue: string | number | null;
        label?: string;
        type?: string;
        error?: string;
        required?: boolean;
        placeholder?: string;
        id?: string;
        autocomplete?: string;
        disabled?: boolean;
    }>(),
    {
        label: '',
        type: 'text',
        error: '',
        required: false,
        placeholder: '',
        id: undefined,
        autocomplete: undefined,
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
        <input
            :id="id"
            :type="type"
            :value="modelValue"
            :placeholder="placeholder"
            :required="required"
            :disabled="disabled"
            :autocomplete="autocomplete"
            :aria-invalid="error ? 'true' : undefined"
            :aria-describedby="error && id ? `${id}-error` : undefined"
            class="field min-h-11"
            :class="{ 'field-error': error }"
            @input="$emit('update:modelValue', ($event.target as HTMLInputElement).value)"
        />
        <p v-if="error" :id="id ? `${id}-error` : undefined" class="mt-1.5 text-sm text-red-600 dark:text-red-400">
            {{ error }}
        </p>
    </div>
</template>
