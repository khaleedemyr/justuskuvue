<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Response $response */
        $response = $next($request);

        // Hardened CSP allowlist for this site stack (Inertia + reCAPTCHA + YouTube embeds).
        $csp = implode('; ', [
            "default-src 'self'",
            "base-uri 'self'",
            "frame-ancestors 'none'",
            "object-src 'none'",
            "form-action 'self'",
            "img-src 'self' data: blob: https:",
            "media-src 'self' data: blob: https:",
            "font-src 'self' data: https://fonts.bunny.net",
            "style-src 'self' 'unsafe-inline' https://fonts.bunny.net",
            "script-src 'self' 'unsafe-inline' https://www.google.com https://www.gstatic.com",
            "frame-src 'self' https://www.google.com https://maps.google.com https://www.gstatic.com https://www.youtube.com https://www.youtube-nocookie.com",
            "connect-src 'self' https://www.google.com https://www.gstatic.com",
            'upgrade-insecure-requests',
        ]);

        $response->headers->set('Content-Security-Policy', $csp, false);
        $response->headers->set('X-Frame-Options', 'DENY', false);
        $response->headers->set('X-Content-Type-Options', 'nosniff', false);
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin', false);
        $response->headers->set('Permissions-Policy', 'geolocation=(), microphone=(), camera=()', false);

        if ($request->isSecure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains; preload', false);
        }

        return $response;
    }
}

