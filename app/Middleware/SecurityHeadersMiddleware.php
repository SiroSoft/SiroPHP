<?php

declare(strict_types=1);

namespace App\Middleware;

use Siro\Core\Request;
use Siro\Core\Response;

/**
 * SecurityHeadersMiddleware - Adds essential security headers to all responses
 *
 * Protects against:
 * - Clickjacking (X-Frame-Options)
 * - MIME type sniffing (X-Content-Type-Options)
 * - XSS attacks (X-XSS-Protection)
 * - Information leakage (Referrer-Policy)
 * - Man-in-the-middle attacks (Strict-Transport-Security)
 * - Content Security Policy violations (CSP)
 */
final class SecurityHeadersMiddleware
{
    /**
     * Handle the request and add security headers
     *
     * @param Request $request The incoming request
     * @param callable $next The next middleware/handler
     * @return Response The response with security headers
     */
    public function handle(Request $request, callable $next): Response
    {
        $response = $next($request);

        // Prevent clickjacking attacks
        $response->header('X-Frame-Options', 'DENY');

        // Prevent MIME type sniffing
        $response->header('X-Content-Type-Options', 'nosniff');

        // Enable XSS filtering in older browsers
        $response->header('X-XSS-Protection', '1; mode=block');

        // Control referrer information
        $response->header('Referrer-Policy', 'strict-origin-when-cross-origin');

        // Permissions Policy (formerly Feature-Policy)
        $response->header('Permissions-Policy', 'geolocation=(), microphone=(), camera=()');

        // Content Security Policy - restrictive by default
        // Adjust based on your application's needs
        $csp = "default-src 'self'; "
             . "script-src 'self' 'unsafe-inline' 'unsafe-eval'; "
             . "style-src 'self' 'unsafe-inline'; "
             . "img-src 'self' data: https:; "
             . "font-src 'self'; "
             . "connect-src 'self'; "
             . "frame-ancestors 'none'; "
             . "base-uri 'self'; "
             . "form-action 'self'";
        
        $response->header('Content-Security-Policy', $csp);

        // HTTP Strict Transport Security (only if HTTPS)
        if ($this->isHttps()) {
            $response->header(
                'Strict-Transport-Security',
                'max-age=31536000; includeSubDomains; preload'
            );
        }

        return $response;
    }

    /**
     * Check if the request is using HTTPS
     *
     * @return bool True if HTTPS is enabled
     */
    private function isHttps(): bool
    {
        // Check various HTTPS indicators
        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
            return true;
        }

        if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') {
            return true;
        }

        if (isset($_SERVER['HTTP_X_FORWARDED_SSL']) && $_SERVER['HTTP_X_FORWARDED_SSL'] === 'on') {
            return true;
        }

        if (isset($_SERVER['SERVER_PORT']) && (int) $_SERVER['SERVER_PORT'] === 443) {
            return true;
        }

        return false;
    }
}
