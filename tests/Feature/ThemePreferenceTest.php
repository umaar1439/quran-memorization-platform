<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * الثيم (نهاري/ليلي):
 * - الاختيار يدوي من المستخدم ولا يتبع إعداد النظام.
 * - الافتراضي نهاري لمن لم يختر شيئاً.
 * - يُطبَّق الصنف `dark` قبل أول رسم لمنع وميض الوضع الخاطئ.
 * هذه الاختبارات تحمي هذا السلوك من التراجع (ويب فقط، بلا منطق PHP).
 */
test('the root view applies the saved theme before the first paint', function () {
    $html = (string) $this->get('/login')->assertOk()->getContent();

    expect($html)->toContain("window.localStorage.getItem('theme') === 'dark'")
        ->and($html)->toContain("root.classList.toggle('dark', isDark)")
        ->and($html)->toContain('root.style.colorScheme = isDark ?');
});

test('the theme colour defaults to the light chrome colour', function () {
    $html = (string) $this->get('/login')->assertOk()->getContent();

    expect($html)->toContain('<meta name="theme-color" content="#0f766e">')
        // لا يوجد تبديل تلقائي حسب النظام بعد الآن
        ->and($html)->not->toContain('prefers-color-scheme');
});

test('the CSP allows the inline theme bootstrap and the web fonts', function () {
    $csp = (string) $this->get('/login')->headers->get('Content-Security-Policy');

    expect($csp)->toContain("script-src 'self' 'unsafe-inline'")
        ->and($csp)->toContain('https://fonts.bunny.net')
        ->and($csp)->toContain('https://cdn.jsdelivr.net');
});
