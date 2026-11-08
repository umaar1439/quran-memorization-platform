<script setup lang="ts">
defineProps<{
    value?: string;
}>();
</script>

<template>
    <label class="block text-sm font-medium text-content-body">
        <span v-if="value">{{ value }}</span>
        <span v-else><slot /></span>
    </label>
</template>
