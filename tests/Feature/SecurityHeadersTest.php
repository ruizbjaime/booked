<?php

it('adds security headers to responses', function (): void {
    $response = $this->get('/login');

    $response->assertHeader('X-Content-Type-Options', 'nosniff');
    $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
    $response->assertHeader('X-XSS-Protection', '0');
    $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
    $response->assertHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
});

it('does not add HSTS header in non-production environments', function (): void {
    $response = $this->get('/login');

    $response->assertHeaderMissing('Strict-Transport-Security');
});
