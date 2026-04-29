<?php

declare(strict_types=1);

namespace App\Middleware;

use Siro\Core\Request;
use Siro\Core\Response;

/**
 * JSON content-type validation middleware.
 *
 * Validates that POST/PUT/PATCH requests have Content-Type:
 * application/json and that the body is valid JSON.
 *
 * @package App\Middleware
 */
final class JsonMiddleware
{
    public function handle(Request $request, callable $next): mixed
    {
        $method = $request->method();

        if (in_array($method, ['POST', 'PUT', 'PATCH'], true)) {
            $contentType = $request->header('content-type', '');
            if ($contentType !== '' && !str_contains(strtolower($contentType), 'application/json')) {
                return Response::error('Content-Type must be application/json', 415);
            }

            // Check for malformed JSON
            $rawBody = file_get_contents('php://input') ?: '';
            if ($rawBody !== '') {
                json_decode($rawBody);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    return Response::error('Invalid JSON format: ' . json_last_error_msg(), 400);
                }
            }
        }

        return $next($request);
    }
}
