<script setup lang="ts">
/**
 * AppIcon — مجموعة أيقونات واحدة للتطبيق كله (stroke موحّد 1.75، شبكة 24px).
 * الغرض: منع تكرار SVG مبعثر في الصفحات وضمان اتساق سماكة الخط والحجم.
 * الأيقونات زخرفية دائماً (aria-hidden)؛ النص المجاور هو ما يوصّل المعنى.
 */
export type IconName =
    | 'dashboard'
    | 'mosque'
    | 'circles'
    | 'user-check'
    | 'graduation-cap'
    | 'users'
    | 'user'
    | 'settings'
    | 'flag'
    | 'chart'
    | 'shield-check'
    | 'book'
    | 'target'
    | 'award'
    | 'file-text'
    | 'calendar'
    | 'clock'
    | 'check-circle'
    | 'alert-triangle'
    | 'info'
    | 'x-circle'
    | 'wifi-off'
    | 'menu'
    | 'close'
    | 'sun'
    | 'moon'
    | 'search'
    | 'chevron'
    | 'plus'
    | 'logout'
    | 'arrow-start'
    | 'sparkles'
    | 'download';

withDefaults(
    defineProps<{
        name: IconName;
        size?: 'sm' | 'md' | 'lg';
    }>(),
    {
        size: 'md',
    },
);

const sizeClasses: Record<string, string> = {
    sm: 'h-4 w-4',
    md: 'h-5 w-5',
    lg: 'h-6 w-6',
};

/* مسارات SVG (Lucide-style). أوامر M المتعددة داخل نفس السلسلة = مسار واحد بفروع. */
const paths: Record<IconName, string> = {
    dashboard: 'M3 3h7v9H3zM14 3h7v5h-7zM14 12h7v9h-7zM3 16h7v5H3z',
    mosque: 'M12 2.5 17 7v2H7V7zM5 11h14v10H5zM10 21v-4a2 2 0 1 1 4 0v4',
    circles:
        'M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2M9 7a4 4 0 1 0 0 8 4 4 0 0 0 0-8M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75',
    'user-check': 'M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2M12 3a4 4 0 1 0 0 8 4 4 0 0 0 0-8M16 11l2 2 4-4',
    'graduation-cap': 'M22 10 12 5 2 10l10 5 10-5zM6 12v5c0 1.66 2.69 3 6 3s6-1.34 6-3v-5',
    users: 'M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2M9 3a4 4 0 1 0 0 8 4 4 0 0 0 0-8M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75',
    user: 'M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2M12 3a4 4 0 1 0 0 8 4 4 0 0 0 0-8',
    settings: 'M4 21v-7M4 10V3M12 21v-9M12 8V3M20 21v-5M20 12V3M1 14h6M9 8h6M17 16h6',
    flag: 'M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1zM4 22v-7',
    chart: 'M3 3v18h18M18 17V9M13 17V5M8 17v-3',
    'shield-check': 'M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1zM9 12l2 2 4-4',
    book: 'M12 7v14M3 18a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1h5a4 4 0 0 1 4 4 4 4 0 0 1 4-4h5a1 1 0 0 1 1 1v13a1 1 0 0 1-1 1h-6a3 3 0 0 0-3 3 3 3 0 0 0-3-3z',
    target: 'M12 22a10 10 0 1 0 0-20 10 10 0 0 0 0 20M12 18a6 6 0 1 0 0-12 6 6 0 0 0 0 12M12 14a2 2 0 1 0 0-4 2 2 0 0 0 0 4',
    award: 'M12 15a6 6 0 1 0 0-12 6 6 0 0 0 0 12M8.21 13.89 7 23l5-3 5 3-1.21-9.12',
    'file-text': 'M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8zM14 2v6h6M16 13H8M16 17H8',
    calendar: 'M8 2v4M16 2v4M3 10h18M5 4h14a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2',
    clock: 'M12 22a10 10 0 1 0 0-20 10 10 0 0 0 0 20M12 6v6l4 2',
    'check-circle': 'M22 11.08V12a10 10 0 1 1-5.93-9.14M22 4 12 14.01l-3-3',
    'alert-triangle':
        'M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0zM12 9v4M12 17h.01',
    info: 'M12 22a10 10 0 1 0 0-20 10 10 0 0 0 0 20M12 16v-4M12 8h.01',
    'x-circle': 'M12 22a10 10 0 1 0 0-20 10 10 0 0 0 0 20M15 9l-6 6M9 9l6 6',
    'wifi-off':
        'M12 20h.01M8.5 16.43a5 5 0 0 1 7 0M5 12.86a12 12 0 0 1 5.17-2.69M2 8.82a16 16 0 0 1 4.17-2.39M22 8.82a16 16 0 0 0-11.29-1.9M2 2l20 20',
    menu: 'M4 6h16M4 12h16M4 18h16',
    close: 'M18 6 6 18M6 6l12 12',
    sun: 'M12 17a5 5 0 1 0 0-10 5 5 0 0 0 0 10M12 1v2M12 21v2M4.22 4.22l1.42 1.42M18.36 18.36l1.42 1.42M1 12h2M21 12h2M4.22 19.78l1.42-1.42M18.36 5.64l1.42-1.42',
    moon: 'M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z',
    search: 'M11 19a8 8 0 1 0 0-16 8 8 0 0 0 0 16M21 21l-4.3-4.3',
    chevron: 'm9 18 6-6-6-6',
    plus: 'M12 5v14M5 12h14',
    logout: 'M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9',
    'arrow-start': 'M19 12H5M12 19l-7-7 7-7',
    sparkles: 'm12 3 1.9 5.1L19 10l-5.1 1.9L12 17l-1.9-5.1L5 10l5.1-1.9z',
    download: 'M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M7 10l5 5 5-5M12 15V3',
};
</script>

<template>
    <svg
        :class="sizeClasses[size]"
        class="shrink-0"
        viewBox="0 0 24 24"
        fill="none"
        stroke="currentColor"
        stroke-width="1.75"
        stroke-linecap="round"
        stroke-linejoin="round"
        aria-hidden="true"
        focusable="false"
    >
        <path :d="paths[name]" />
    </svg>
</template>
