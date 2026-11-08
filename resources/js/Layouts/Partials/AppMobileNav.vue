<script setup lang="ts">
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import AppIcon from '@/Components/AppIcon.vue';
import { useNavigation } from '@/Composables/useNavigation';

/**
 * شريط التنقل السفلي على الجوال (أقل من md).
 * أربعة عناصر كحد أقصى في متناول الإبهام + "المزيد" لفتح الدرج ببقية الصفحات،
 * بدل حصر كل التنقل خلف زر قائمة في الأعلى. يحترم المنطقة الآمنة أسفل الشاشة.
 */
const emit = defineEmits<{ 'open-more': [] }>();

const { items, isActive } = useNavigation();

const primaryItems = computed(() => items.value.slice(0, 4));
const hasMore = computed(() => items.value.length > 4);
</script>

<template>
    <nav
        v-if="primaryItems.length > 0"
        class="fixed inset-x-0 bottom-0 z-nav border-t border-edge bg-surface/95 pb-safe backdrop-blur md:hidden"
        aria-label="التنقل الرئيسي"
    >
        <div class="flex items-stretch">
            <Link
                v-for="item in primaryItems"
                :key="item.routeName"
                :href="route(item.routeName)"
                class="focus-ring relative flex min-h-[3.5rem] flex-1 flex-col items-center justify-center gap-1 px-1 py-2 text-[11px] font-medium"
                :class="isActive(item.routeName) ? 'text-teal-700 dark:text-teal-300' : 'text-content-muted'"
                :aria-current="isActive(item.routeName) ? 'page' : undefined"
            >
                <span
                    v-if="isActive(item.routeName)"
                    class="absolute inset-x-4 top-0 h-0.5 rounded-full bg-teal-700 dark:bg-teal-300"
                    aria-hidden="true"
                />
                <AppIcon :name="item.icon" size="lg" />
                <span class="w-full truncate text-center">{{ item.label }}</span>
            </Link>

            <button
                v-if="hasMore"
                type="button"
                class="focus-ring flex min-h-[3.5rem] flex-1 flex-col items-center justify-center gap-1 px-1 py-2 text-[11px] font-medium text-content-muted"
                @click="emit('open-more')"
            >
                <AppIcon name="menu" size="lg" />
                <span>المزيد</span>
            </button>
        </div>
    </nav>
</template>
