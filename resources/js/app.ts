import '../css/app.css';
import './bootstrap';

import { config, createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createApp, DefineComponent, h } from 'vue';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';
import { initTheme } from '@/Composables/useTheme';
import { initInstallPrompt } from '@/Composables/useInstallPrompt';
import { registerServiceWorker } from '@/Composables/useServiceWorker';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

// مزامنة حالة الثيم مع الصنف الذي طبّقه السكربت المضمّن في <head>
// (الافتراضي نهاري، والاختيار محفوظ في متصفح المستخدم).
initTheme();

// PWA: تسجيل Service Worker (في الإنتاج فقط) والتقاط دعوة التثبيت.
// التحديث لا يُطبَّق تلقائياً؛ PwaUpdateBanner يطلبه من المستخدم صراحةً.
registerServiceWorker();
initInstallPrompt();

/*
 * View Transitions — انتقال ناعم بين الصفحات.
 *
 * Inertia (≥ 2.3) ينفّذ تبديل الصفحة داخل document.startViewTransition() عند
 * توفّره، ويتجاوزه تلقائياً إلى تبديل عادي بلا حركة في المتصفحات التي لا تدعمه
 * أو حين يكون التبويب مخفياً — فلا حاجة لكشف الميزة يدوياً هنا.
 * التنسيق البصري (fade + translateY) في resources/css/app.css.
 *
 * نستثني تحديثات الصفحة التي تحفظ حالتها (بحث/ترقيم صفحات/فلاتر) لأنها تحديث
 * بيانات داخل الصفحة نفسها وليست انتقالاً: جداول البيانات تحرّك صفوفها بنفسها،
 * وتلاشي الصفحة كاملة عند كل حرف بحث مضر لا مفيد.
 * ونحترم "تقليل الحركة" من طبقة المنطق أيضاً، لا اعتماداً على CSS وحده.
 */
const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');

config.set('visitOptions', (_href, options) => ({
    viewTransition: options.viewTransition ?? (!options.preserveState && !prefersReducedMotion.matches),
}));

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.vue`,
            import.meta.glob<DefineComponent>('./Pages/**/*.vue'),
        ),
    setup({ el, App, props, plugin }) {
        createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue)
            .mount(el);
    },
    progress: {
        color: '#4B5563',
    },
});
