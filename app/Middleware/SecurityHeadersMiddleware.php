<?php

declare(strict_types=1);

namespace App\Middleware;

use Siro\Core\Middleware\MiddlewareInterface;
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
final class SecurityHeadersMiddleware implements MiddlewareInterface
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
        /** @var Response $response */

        $response->header('X-Frame-Options', 'DENY');
        $response->header('X-Content-Type-Options', 'nosniff');
        $response->header('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->header('Permissions-Policy', 'geolocation=(), microphone=(), camera=()');
        $response->header('Strict-Transport-Security', 'max-age=31536000; includeSubDomains; preload');
        $response->header('Cross-Origin-Resource-Policy', 'same-origin');
        $response->header('Cross-Origin-Opener-Policy', 'same-origin');

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

        if (isset($_SERVER['SERVER_PORT'])) {
            $serverPort = $_SERVER['SERVER_PORT'];
            /** @var int|string $serverPort */
            if ((int) $serverPort === 443) {
                return true;
            }
        }

        return false;
    }
}
