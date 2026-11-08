<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ in_array(app()->getLocale(), ['ar']) ? 'rtl' : 'ltr' }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">

        <title inertia>{{ config('app.name', 'Laravel') }}</title>

        <!-- PWA (Phase 12: PWA والمزامنة Offline) -->
        <link rel="manifest" href="/manifest.json">
        <!-- لون شريط المتصفح على الجوال: يتبع الوضع الفاتح/الليلي للنظام -->
        <meta name="theme-color" content="#0f766e" media="(prefers-color-scheme: light)">
        <meta name="theme-color" content="#111827" media="(prefers-color-scheme: dark)">
        <link rel="icon" href="/icons/icon-192.png">
        <link rel="apple-touch-icon" href="/icons/icon-192.png">

        <!-- Fonts: IBM Plex Sans Arabic (العربية + اللاتينية) مع Figtree كاحتياطي -->
        <link rel="preconnect" href="https://fonts.bunny.net" crossorigin>
        <link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>
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
