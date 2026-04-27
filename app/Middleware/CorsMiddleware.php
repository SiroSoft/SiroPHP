<?php

declare(strict_types=1);

namespace App\Middleware;

use Siro\Core\Env;
use Siro\Core\Request;
use Siro\Core\Response;

final class CorsMiddleware
{
    public function handle(Request $request, callable $next): mixed
    {
        $allowedOrigins = (string) Env::get('CORS_ALLOWED_ORIGINS', '*');
        $allowedMethods = (string) Env::get('CORS_ALLOWED_METHODS', 'GET,POST,PUT,DELETE,OPTIONS');
        $allowedHeaders = (string) Env::get('CORS_ALLOWED_HEADERS', 'Content-Type,Authorization,X-Requested-With');

        $origin = $request->header('origin', '*');
        $allowOrigin = $allowedOrigins === '*' ? '*' : $this->resolveOrigin($origin, $allowedOrigins);

        if ($request->method() === 'OPTIONS') {
            $response = Response::noContent();
            $this->appendHeaders($allowOrigin, $allowedMethods, $allowedHeaders);
            return $response;
        }

        $result = $next($request);
        $this->appendHeaders($allowOrigin, $allowedMethods, $allowedHeaders);

        return $result;
    }

    private function appendHeaders(string $allowOrigin, string $allowedMethods, string $allowedHeaders): void
    {
        header('Access-Control-Allow-Origin: ' . $allowOrigin);
        header('Access-Control-Allow-Methods: ' . $allowedMethods);
        header('Access-Control-Allow-Headers: ' . $allowedHeaders);
        header('Access-Control-Allow-Credentials: true');
        header('Vary: Origin');
    }

    private function resolveOrigin(string $origin, string $allowedOrigins): string
    {
        $origins = array_map('trim', explode(',', $allowedOrigins));
        return in_array($origin, $origins, true) ? $origin : $origins[0];
    }
}
