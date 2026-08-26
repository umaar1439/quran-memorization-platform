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
        <label v-if="label" :for="id" class="mb-1 block text-sm font-medium text-gray-700">
            {{ label }}
            <span v-if="required" class="text-red-600">*</span>
        </label>
        <select
            :id="id"
            :value="modelValue ?? ''"
            :required="required"
            :disabled="disabled"
            class="block min-h-[44px] w-full rounded-md border-gray-300 shadow-sm focus:border-teal-500 focus:ring-teal-500 sm:text-sm"
            :class="{ 'border-red-500': error, 'cursor-not-allowed bg-gray-100 text-gray-500': disabled }"
            @change="$emit('update:modelValue', ($event.target as HTMLSelectElement).value)"
        >
            <option value="" disabled>{{ placeholder }}</option>
            <option v-for="option in options" :key="option.value" :value="option.value">
                {{ option.label }}
            </option>
        </select>
        <p v-if="error" class="mt-1 text-sm text-red-600">{{ error }}</p>
    </div>
</template>
