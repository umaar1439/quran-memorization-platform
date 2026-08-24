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
    }>(),
    {
        label: '',
        type: 'text',
        error: '',
        required: false,
        placeholder: '',
        id: undefined,
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
        <input
            :id="id"
            :type="type"
            :value="modelValue"
            :placeholder="placeholder"
            :required="required"
            class="block min-h-[44px] w-full rounded-md border-gray-300 shadow-sm focus:border-teal-500 focus:ring-teal-500 sm:text-sm"
            :class="{ 'border-red-500': error }"
            @input="$emit('update:modelValue', ($event.target as HTMLInputElement).value)"
        />
        <p v-if="error" class="mt-1 text-sm text-red-600">{{ error }}</p>
    </div>
</template>
