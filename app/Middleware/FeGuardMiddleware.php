<?php

declare(strict_types=1);

namespace App\Middleware;

use Siro\Core\Middleware\MiddlewareInterface;
use Siro\Core\Request;
use Siro\Core\Response;

/**
 * FeGuardMiddleware - restricts /api access to known frontends.
 *
 * Every /api request must carry header X-Siro-FE matching the
 * FE_SHARED_SECRET env value (hash_equals). Missing/mismatched → 403.
 *
 * Fail-open when FE_SHARED_SECRET is not set (local dev without the
 * secret configured). Set the secret in production (.env) to enforce.
 * OPTIONS preflight requests always pass (CORS handled separately).
 */
final class FeGuardMiddleware implements MiddlewareInterface
{
    public const HEADER = 'X-Siro-FE';

    /**
     * @param Request $request The incoming request
     * @param callable $next The next middleware/handler
     * @return mixed Next response or 403 when the FE token is invalid
     */
    public function handle(Request $request, callable $next): mixed
    {
        if (strtoupper($request->method()) === 'OPTIONS') {
            return $next($request);
        }

        $secret = (string) (\Siro\Core\Env::get('FE_SHARED_SECRET', ''));
        if ($secret === '') {
            return $next($request);
        }

        $given = (string) $request->header('x-siro-fe', '');
        if ($given === '' || !hash_equals($secret, $given)) {
            return Response::error('Forbidden', 403, [
                'fe' => ['Unknown frontend. Missing or invalid client token.'],
            ]);
        }

        return $next($request);
    }
}
