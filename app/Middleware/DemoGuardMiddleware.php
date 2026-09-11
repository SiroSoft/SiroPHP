<?php

declare(strict_types=1);

namespace App\Middleware;

use Siro\Core\Middleware\MiddlewareInterface;
use Siro\Core\Request;
use Siro\Core\Response;

/**
 * DemoGuardMiddleware - blocks write operations for the demo viewer account.
 *
 * The public demo user (role "viewer", DEMO_EMAIL) may browse (GET) but not
 * mutate: POST/PUT/PATCH/DELETE return 403 demo.readonly. Safe methods pass.
 * Attach after "auth" on mutating routes, or rely on routes/api.php wiring.
 */
final class DemoGuardMiddleware implements MiddlewareInterface
{
    /** @var array<int, string> */
    private const READONLY_METHODS = ['POST', 'PUT', 'PATCH', 'DELETE'];

    /**
     * @param Request $request The incoming request
     * @param callable $next The next middleware/handler
     * @return mixed Next response or 403 for demo writes
     */
    public function handle(Request $request, callable $next): mixed
    {
        $method = strtoupper($request->method());
        if (!in_array($method, self::READONLY_METHODS, true)) {
            return $next($request);
        }

        $user = $request->user();
        $role = is_array($user) && isset($user['role']) ? (string) $user['role'] : '';
        $demoEmail = (string) (\Siro\Core\Env::get('DEMO_EMAIL', 'demo@skeleton.sirophp.com'));
        $email = is_array($user) && isset($user['email']) ? (string) $user['email'] : '';

        if ($role === 'viewer' || ($demoEmail !== '' && $email === $demoEmail)) {
            return Response::error('Forbidden', 403, [
                'demo' => ['Demo account is read-only. Deploy your own skeleton to write.'],
            ]);
        }

        return $next($request);
    }
}
