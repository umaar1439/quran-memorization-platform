import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';
import rtl from 'tailwindcss-rtl';

/** @type {import('tailwindcss').Config} */
export default {
    /*
     * الوضع الليلي اختياري بالكامل: يُفعّل بإضافة الصنف `dark` على <html>
     * (انظر resources/js/Composables/useTheme.ts)، ويُحفظ اختيار المستخدم في
     * متصفحه، والافتراضي نهاري دائماً. قيم الألوان في resources/css/app.css
     * عبر متغيّرات CSS، فتتبدّل تلقائياً مع الصنف.
     */
    darkMode: 'class',

    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.vue',
    ],

    theme: {
        extend: {
            /*
             * الخط: IBM Plex Sans Arabic يغطّي العربية واللاتينية بوزن واحد متّسق،
             * ويأتي أولاً لأن الواجهة عربية أولاً؛ Figtree خط احتياطي.
             * لا نضيف letter-spacing على أي نص عربي (يكسر اتصال الحروف).
             */
            fontFamily: {
                sans: ['"IBM Plex Sans Arabic"', 'Figtree', ...defaultTheme.fontFamily.sans],
            },

            /*
             * ظلال متعددة الطبقات بدل الظل الواحد الثقيل: حافة 1px + ظل ناعم
             * بعيد يعطي عمقاً بلا ثقل. في الوضع الليلي يقوم الحدّ (edge) بالمهمة.
             */
            boxShadow: {
                card: '0 1px 2px 0 rgb(15 23 42 / 0.04), 0 10px 24px -18px rgb(15 23 42 / 0.20)',
                raised: '0 1px 3px 0 rgb(15 23 42 / 0.06), 0 14px 32px -20px rgb(15 23 42 / 0.28)',
                pop: '0 24px 60px -28px rgb(15 23 42 / 0.38)',
            },

            /* طبقات z-index مركزية حتى لا تتضارب الأشرطة والنوافذ والإشعارات */
            zIndex: {
                nav: '30',
                drawer: '40',
                modal: '50',
                toast: '60',
            },

            /* منحنى حركة موحّد (120-250ms) لكل التفاعلات الدقيقة */
            transitionTimingFunction: {
                standard: 'cubic-bezier(0.2, 0, 0, 1)',
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
                /* طبقة التعتيم خلف النوافذ والأدراج (scrim) */
                scrim: 'rgb(var(--color-scrim) / <alpha-value>)',
            },
        },
    },

    // دعم RTL عبر tailwindcss-rtl (لأن الإصدار المستخدم من Tailwind هو v3 عبر Breeze)
    plugins: [
        forms,
        rtl,
        /*
         * متغيّر `coarse:` = أجهزة المؤشر الخشن (اللمس: هاتف/جهاز لوحي).
         * يُستخدم لتكبير هدف اللمس إلى 44px على هذه الأجهزة فقط، فلا تتضخّم
         * كثافة واجهة الحاسب (فأرة) ولا ترتفع صفوف الجداول على الشاشات الكبيرة.
         * مثال: coarse:min-h-11
         */
        ({ addVariant }) => {
            addVariant('coarse', '@media (pointer: coarse)');
        },
    ],
};
