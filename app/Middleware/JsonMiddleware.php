<?php

declare(strict_types=1);

namespace App\Middleware;

use Siro\Core\Request;
use Siro\Core\Response;

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
        }

        return $next($request);
    }
}
