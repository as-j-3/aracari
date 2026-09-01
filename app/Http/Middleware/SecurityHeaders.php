<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        if ($request->isMethod('OPTIONS')) {
            return $response;
        }
        $response->headers->remove('X-Powered-By');
        $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        $response->headers->set('Permissions-Policy', 'geolocation=(), camera=(), microphone=(), payment=()');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        if ($request->isMethod('GET') && $this->isHtmlRequest($request) && !$this->shouldSkipHtmlSecurityHeaders($response)) {
            $nonce = base64_encode(random_bytes(16));
            $request->attributes->set('csp_nonce', $nonce);

            $csp = "default-src 'self'; " .
                   "script-src 'self' 'nonce-{$nonce}' https://cdn.tailwindcss.com https://unpkg.com https://cdn.jsdelivr.net; " .
                   "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdnjs.cloudflare.com; " .
                   "img-src 'self' data: https://cdnjs.cloudflare.com; " .
                   "font-src 'self' data: https://fonts.gstatic.com https://cdnjs.cloudflare.com; " .
                   "connect-src 'self'; " .
                   "frame-ancestors 'self'; " .
                   "base-uri 'self'; " .
                   "form-action 'self';";

            $response->headers->set('Content-Security-Policy-Report-Only', $csp);
            $response->headers->set('Content-Security-Policy', $csp);
        }

        return $response;
    }

    protected function isHtmlRequest(Request $request): bool
    {
        return $request->acceptsHtml() || $request->header('Accept') === '*/*' || !$request->expectsJson();
    }

    protected function isHtmlResponse($response): bool
    {
        $contentType = $response->headers->get('Content-Type');
        return $contentType && str_contains($contentType, 'text/html');
    }

    protected function shouldSkipHtmlSecurityHeaders($response): bool
    {
        return config('app.debug')
            && $response->getStatusCode() >= 500
            && $this->isHtmlResponse($response);
    }
}