import '../css/app.css';
import './bootstrap';

import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createApp, DefineComponent, h } from 'vue';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';
import { initTheme } from '@/Composables/useTheme';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

// مزامنة حالة الثيم مع الصنف الذي طبّقه السكربت المضمّن في <head>
// (الافتراضي نهاري، والاختيار محفوظ في متصفح المستخدم).
initTheme();

// تسجيل Service Worker للعمل دون اتصال (Phase 12: PWA والمزامنة Offline).
if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js').catch(() => {
            // فشل التسجيل لا يجب أن يوقف تحميل التطبيق.
        });
    });
}

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
