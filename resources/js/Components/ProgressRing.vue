<script setup lang="ts">
import { computed } from 'vue';

const props = withDefaults(
    defineProps<{
        percentage: number;
        size?: number;
        strokeWidth?: number;
        label?: string;
        color?: string;
    }>(),
    {
        size: 140,
        strokeWidth: 12,
        label: '',
        color: '#0d9488',
    },
);

const clamped = computed(() => Math.min(100, Math.max(0, props.percentage)));
const radius = computed(() => (props.size - props.strokeWidth) / 2);
const circumference = computed(() => 2 * Math.PI * radius.value);
const offset = computed(() => circumference.value * (1 - clamped.value / 100));
const center = computed(() => props.size / 2);
</script>

<template>
    <div class="inline-flex flex-col items-center" role="img" :aria-label="`${label} ${clamped}%`">
        <svg :width="size" :height="size" :viewBox="`0 0 ${size} ${size}`">
            <circle
                :cx="center"
                :cy="center"
                :r="radius"
                fill="none"
                stroke="rgb(var(--color-edge))"
                :stroke-width="strokeWidth"
            />
            <circle
                :cx="center"
                :cy="center"
                :r="radius"
                fill="none"
                :stroke="color"
                :stroke-width="strokeWidth"
                stroke-linecap="round"
                :stroke-dasharray="circumference"
                :stroke-dashoffset="offset"
                :transform="`rotate(-90 ${center} ${center})`"
                class="transition-all duration-[250ms]"
            />
            <text
                :x="center"
                :y="center"
                text-anchor="middle"
                dominant-baseline="middle"
                class="fill-content text-2xl font-bold"
            >
                {{ clamped }}%
            </text>
        </svg>
        <span v-if="label" class="mt-2 text-sm text-content-soft">{{ label }}</span>
    </div>
</template>
