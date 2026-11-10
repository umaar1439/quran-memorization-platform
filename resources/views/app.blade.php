<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ in_array(app()->getLocale(), ['ar']) ? 'rtl' : 'ltr' }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">

        <title inertia>{{ config('app.name', 'Laravel') }}</title>

        <!-- PWA: التثبيت والعمل دون اتصال (manifest + sw.js + صفحة offline) -->
        <link rel="manifest" href="/manifest.json">
        <!-- لون شريط المتصفح على الجوال: يحدّثه سكربت الثيم أدناه حسب اختيار المستخدم -->
        <meta name="theme-color" content="#0f766e">
        <meta name="mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-status-bar-style" content="default">
        <meta name="apple-mobile-web-app-title" content="تحفيظ">
        <link rel="icon" href="/favicon.ico" sizes="32x32">
        <link rel="icon" href="/icons/icon-192.png" type="image/png" sizes="192x192">
        <link rel="apple-touch-icon" sizes="192x192" href="/icons/icon-192.png">

        <!--
            الثيم: الافتراضي نهاري، والوضع الليلي يُفعّل فقط باختيار المستخدم
            (يُحفظ في localStorage). يُطبَّق هنا قبل تحميل الأنماط لمنع وميض
            الوضع النهاري عند فتح التطبيق في وضع ليلي.
        -->
        <script>
            (function () {
                try {
                    var isDark = window.localStorage.getItem('theme') === 'dark';
                    var root = document.documentElement;

                    root.classList.toggle('dark', isDark);
                    root.style.colorScheme = isDark ? 'dark' : 'light';

                    var meta = document.querySelector('meta[name="theme-color"]');
                    if (meta) {
                        meta.setAttribute('content', isDark ? '#111827' : '#0f766e');
                    }
                } catch (e) {
                    // التخزين المحلي معطّل (تصفح متخفٍّ): يبقى الوضع النهاري الافتراضي.
                }
            })();
        </script>

        <!-- Fonts: IBM Plex Sans Arabic (العربية + اللاتينية) مع Figtree كاحتياطي -->
        <link rel="preconnect" href="https://fonts.bunny.net" crossorigin>
        <link
            href="https://fonts.bunny.net/css?family=figtree:400,500,600,700|ibm-plex-sans-arabic:400,500,600,700&display=swap"
            rel="stylesheet"
        />

        <!-- Scripts -->
        @routes
        @vite(['resources/js/app.ts', "resources/js/Pages/{$page['component']}.vue"])
        @inertiaHead
    </head>
    <body class="font-sans antialiased">
        @inertia
    </body>
</html>
