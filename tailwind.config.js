import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';
import rtl from 'tailwindcss-rtl';

/** @type {import('tailwindcss').Config} */
export default {
    /*
     * الوضع الليلي يتبع إعداد النظام تلقائياً (لا حاجة لتبديل يدوي أو JS)،
     * وتُعرَّف قيم الألوان في resources/css/app.css عبر متغيرات CSS.
     */
    darkMode: 'media',

    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.vue',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },

            /*
             * ألوان دلالية مركزية (Design Tokens): تُقرأ قيمها من متغيرات CSS
             * في resources/css/app.css، وتتبدّل تلقائياً في الوضع الليلي.
             * استخدم هذه الأسماء في المكوّنات بدل أصناف gray/white المباشرة:
             *   surface        = خلفية البطاقات/الحاويات
             *   surface-muted  = خلفية الصفحة أو رؤوس الجداول
             *   surface-sunken = خلفية أغمق درجة (صفوف، أشرطة مسار)
             *   content        = نص العناوين
             *   content-*      = تدرّج النص من الأقوى إلى الأخفت
             *   edge-*         = الحدود والفواصل
             */
            colors: {
                surface: {
                    DEFAULT: 'rgb(var(--color-surface) / <alpha-value>)',
                    muted: 'rgb(var(--color-surface-muted) / <alpha-value>)',
                    sunken: 'rgb(var(--color-surface-sunken) / <alpha-value>)',
                },
                content: {
                    DEFAULT: 'rgb(var(--color-content) / <alpha-value>)',
                    strong: 'rgb(var(--color-content-strong) / <alpha-value>)',
                    body: 'rgb(var(--color-content-body) / <alpha-value>)',
                    soft: 'rgb(var(--color-content-soft) / <alpha-value>)',
                    muted: 'rgb(var(--color-content-muted) / <alpha-value>)',
                    faint: 'rgb(var(--color-content-faint) / <alpha-value>)',
                },
                edge: {
                    DEFAULT: 'rgb(var(--color-edge) / <alpha-value>)',
                    strong: 'rgb(var(--color-edge-strong) / <alpha-value>)',
                    soft: 'rgb(var(--color-edge-soft) / <alpha-value>)',
                },
            },
        },
    },

    // دعم RTL عبر tailwindcss-rtl (لأن الإصدار المستخدم من Tailwind هو v3 عبر Breeze)
    plugins: [forms, rtl],
};
