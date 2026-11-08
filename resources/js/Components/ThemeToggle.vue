<script setup lang="ts">
import { ref } from 'vue';
import AppIcon from '@/Components/AppIcon.vue';
import { useTheme } from '@/Composables/useTheme';

/**
 * ThemeToggle — زر تبديل الوضع الليلي/النهاري.
 * - يعرض أيقونة الوضع الذي سيُنتقل إليه (شمس في الليلي، قمر في النهاري)
 *   مع aria-pressed ليعرف قارئ الشاشة الحالة الحالية.
 * - هدف لمس 44px كما في بقية أزرار الأيقونات في الشريط العلوي.
 * - التغيير يُعلَن مرة واحدة عبر منطقة حيّة (بلا إزعاج عند تحميل الصفحة).
 */
const { isDark, toggleTheme } = useTheme();

const announcement = ref('');

function onChange(): void {
    toggleTheme();
    announcement.value = isDark.value ? 'تم تفعيل الوضع الليلي' : 'تم تفعيل الوضع النهاري';
}
</script>

<template>
    <button
        type="button"
        class="focus-ring inline-flex h-11 w-11 items-center justify-center rounded-lg text-content-body transition-colors duration-150 ease-standard hover:bg-surface-sunken"
        :aria-pressed="isDark"
        :aria-label="isDark ? 'التبديل إلى الوضع النهاري' : 'التبديل إلى الوضع الليلي'"
        :title="isDark ? 'الوضع النهاري' : 'الوضع الليلي'"
        @click="onChange"
    >
        <AppIcon :name="isDark ? 'sun' : 'moon'" />
    </button>

    <span class="sr-only" role="status" aria-live="polite">{{ announcement }}</span>
</template>
