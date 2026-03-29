<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    /**
     * Headers applied to every web response.
     *
     * @var array<string, string>
     */
    private const HEADERS = [
        'X-Content-Type-Options' => 'nosniff',
        'X-Frame-Options' => 'SAMEORIGIN',
        'X-XSS-Protection' => '0',
        'Referrer-Policy' => 'strict-origin-when-cross-origin',
        'Permissions-Policy' => 'camera=(), microphone=(), geolocation=()',
    ];

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        foreach (self::HEADERS as $key => $value) {
            $response->headers->set($key, $value);
        }

        if (app()->isProduction()) {
            $response->headers->set('Content-Security-Policy', $this->csp());
            $response->headers->set(
                'Strict-Transport-Security',
                'max-age=31536000; includeSubDomains',
            );
        }

        return $response;
    }

    private function csp(): string
    {
        return implode('; ', [
            "default-src 'self'",
            // unsafe-inline required by Livewire/Alpine inline scripts; nonce-based CSP not yet supported
            "script-src 'self' 'unsafe-inline'",
            "style-src 'self' 'unsafe-inline' https://fonts.bunny.net",
            "img-src 'self' data: blob:",
            "font-src 'self' https://fonts.bunny.net",
            "connect-src 'self'",
            "frame-ancestors 'self'",
        ]);
    }
}
