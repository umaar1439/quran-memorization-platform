<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('security headers are attached to every web response', function () {
    $response = $this->get('/login')->assertOk();

    $response->assertHeader('X-Frame-Options', 'DENY');
    $response->assertHeader('X-Content-Type-Options', 'nosniff');
    $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');

    $csp = (string) $response->headers->get('Content-Security-Policy');

    expect($csp)->toContain("default-src 'self'")
        ->and($csp)->toContain("frame-ancestors 'none'")
        ->and($csp)->toContain("base-uri 'self'");
});

test('HSTS is not sent outside production', function () {
    $response = $this->get('/login');

    expect(app()->isProduction())->toBeFalse()
        ->and($response->headers->has('Strict-Transport-Security'))->toBeFalse();
});

test('the development CSP still allows the Vite HMR websocket', function () {
    $csp = (string) $this->get('/login')->headers->get('Content-Security-Policy');

    expect($csp)->toContain('ws:')
        ->and($csp)->toContain("'unsafe-eval'");
});
