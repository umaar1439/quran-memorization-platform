<script setup lang="ts">
import { computed } from 'vue';

const emit = defineEmits(['update:checked']);

const props = withDefaults(
    defineProps<{
        checked: boolean;
        value?: any;
        disabled?: boolean;
    }>(),
    {
        disabled: false,
    },
);

const proxyChecked = computed({
    get() {
        return props.checked;
    },

    set(val) {
        emit('update:checked', val);
    },
});
</script>

<template>
    <input
        type="checkbox"
        :value="value"
        :disabled="disabled"
        v-model="proxyChecked"
        class="h-4 w-4 rounded border-edge-strong bg-surface text-teal-700 shadow-sm focus:ring-2 focus:ring-teal-600/40 disabled:cursor-not-allowed disabled:opacity-60 dark:bg-surface-sunken dark:text-teal-300"
    />
</template>
