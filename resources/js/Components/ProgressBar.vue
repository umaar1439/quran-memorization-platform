<script setup lang="ts">
import { computed } from 'vue';

const props = withDefaults(
    defineProps<{
        value: number;
        max?: number;
        label?: string;
        color?: 'teal' | 'blue' | 'green' | 'amber' | 'red';
        showPercentage?: boolean;
    }>(),
    {
        max: 100,
        label: '',
        color: 'teal',
        showPercentage: true,
    },
);

const percentage = computed(() => {
    if (props.max <= 0) {
        return 0;
    }

    return Math.min(100, Math.max(0, Math.round((props.value / props.max) * 100)));
});

const colorClasses: Record<string, string> = {
    teal: 'bg-teal-600',
    blue: 'bg-blue-600',
    green: 'bg-green-600',
    amber: 'bg-amber-500',
    red: 'bg-red-600',
};
</script>

<template>
    <div class="w-full">
        <div v-if="label || showPercentage" class="mb-1 flex items-center justify-between text-sm">
            <span v-if="label" class="text-content-soft">{{ label }}</span>
            <span v-if="showPercentage" class="font-medium text-content-strong">{{ percentage }}%</span>
        </div>
        <div class="h-2.5 w-full overflow-hidden rounded-full bg-edge">
            <div
                :class="colorClasses[color]"
                :style="{ width: `${percentage}%` }"
                class="h-full rounded-full transition-all duration-200"
            />
        </div>
    </div>
</template>
