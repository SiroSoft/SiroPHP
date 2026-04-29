<?php

declare(strict_types=1);

namespace App\Middleware;

use Siro\Core\Env;
use Siro\Core\Request;
use Siro\Core\Response;

/**
 * CORS middleware.
 *
 * Handles CORS preflight OPTIONS requests and appends
 * Access-Control-* headers to all responses. Supports specific
 * origin resolution and credentials.
 *
 * @package App\Middleware
 */
final class CorsMiddleware
{
    public function handle(Request $request, callable $next): mixed
    {
        $allowedOrigins = (string) Env::get('CORS_ALLOWED_ORIGINS', '*');
        $allowedMethods = (string) Env::get('CORS_ALLOWED_METHODS', 'GET,POST,PUT,DELETE,OPTIONS');
        $allowedHeaders = (string) Env::get('CORS_ALLOWED_HEADERS', 'Content-Type,Authorization,X-Requested-With');

        $origin = (string) $request->header('origin', '');
        $allowOrigin = $allowedOrigins === '*' ? ($origin !== '' ? $origin : '*') : $this->resolveOrigin($origin, $allowedOrigins);
        $allowCredentials = $allowedOrigins !== '*';

        if ($request->method() === 'OPTIONS') {
            $response = Response::noContent();
            $this->appendHeaders($allowOrigin, $allowedMethods, $allowedHeaders, $allowCredentials);
            return $response;
        }

        $result = $next($request);
        $this->appendHeaders($allowOrigin, $allowedMethods, $allowedHeaders, $allowCredentials);

        return $result;
    }

    private function appendHeaders(string $allowOrigin, string $allowedMethods, string $allowedHeaders, bool $allowCredentials): void
    {
        header('Access-Control-Allow-Origin: ' . $allowOrigin);
        header('Access-Control-Allow-Methods: ' . $allowedMethods);
        header('Access-Control-Allow-Headers: ' . $allowedHeaders);
        header('Access-Control-Allow-Credentials: ' . ($allowCredentials ? 'true' : 'false'));
        header('Vary: Origin');
    }

    private function resolveOrigin(string $origin, string $allowedOrigins): string
    {
        $origins = array_map('trim', explode(',', $allowedOrigins));
        return in_array($origin, $origins, true) ? $origin : $origins[0];
    }
}
