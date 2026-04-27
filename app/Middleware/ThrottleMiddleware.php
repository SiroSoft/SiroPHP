<?php

declare(strict_types=1);

namespace App\Middleware;

use Siro\Core\Env;
use Siro\Core\Request;
use Siro\Core\Response;
use Throwable;

final class ThrottleMiddleware
{
    private const FALLBACK_DISABLED = 'disabled';
    private const FALLBACK_FAIL_CLOSED = 'fail_closed';
    private const FALLBACK_FILE = 'file';

    private ?\Redis $redis = null;
    private bool $resolved = false;

    public function handle(Request $request, callable $next, string $maxRequests = '60', string $minutes = '1'): mixed
    {
        $limit = max(1, (int) $maxRequests);
        $windowMinutes = max(1, (int) $minutes);
        $ttl = $windowMinutes * 60;

        $redis = $this->redis();
        if (!$redis instanceof \Redis) {
            return $this->handleFallback($request, $next, $limit, $windowMinutes, $ttl);
        }

        $ip = $request->ip();
        $route = rawurlencode($request->method() . ':' . $request->path());
        $key = sprintf('rate:%s:%s', $ip, $route);

        try {
            $count = (int) $redis->incr($key);
            if ($count === 1) {
                $redis->expire($key, $ttl);
            }

            $remaining = max(0, $limit - $count);
            $retryAfter = max(0, (int) $redis->ttl($key));

            header('X-RateLimit-Limit: ' . $limit);
            header('X-RateLimit-Remaining: ' . $remaining);
            if ($retryAfter > 0) {
                header('X-RateLimit-Reset: ' . (time() + $retryAfter));
            }

            if ($count > $limit) {
                if ($retryAfter > 0) {
                    header('Retry-After: ' . $retryAfter);
                }

                return Response::error('Too Many Requests', 429, [
                    'throttle' => [sprintf('Rate limit exceeded. Max %d requests per %d minute(s).', $limit, $windowMinutes)],
                ]);
            }
        } catch (Throwable) {
            return $this->handleFallback($request, $next, $limit, $windowMinutes, $ttl);
        }

        return $next($request);
    }

    private function handleFallback(Request $request, callable $next, int $limit, int $windowMinutes, int $ttl): mixed
    {
        $strategy = strtolower((string) Env::get('THROTTLE_FALLBACK', self::FALLBACK_FILE));

        if ($strategy === self::FALLBACK_DISABLED) {
            return $next($request);
        }

        if ($strategy === self::FALLBACK_FAIL_CLOSED) {
            return Response::error('Too Many Requests', 429, [
                'throttle' => ['Rate limiter backend unavailable'],
            ]);
        }

        return $this->enforceFileFallback($request, $next, $limit, $windowMinutes, $ttl);
    }

    private function enforceFileFallback(Request $request, callable $next, int $limit, int $windowMinutes, int $ttl): mixed
    {
        $ip = $request->ip();
        $route = rawurlencode($request->method() . ':' . $request->path());
        $key = sprintf('rate:%s:%s', $ip, $route);

        $storeDir = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'rate_limit';
        if (!is_dir($storeDir)) {
            mkdir($storeDir, 0775, true);
        }

        $file = $storeDir . DIRECTORY_SEPARATOR . sha1($key) . '.json';
        $now = time();

        $fp = fopen($file, 'c+');
        if ($fp === false) {
            return Response::error('Too Many Requests', 429, [
                'throttle' => ['Rate limiter fallback storage unavailable'],
            ]);
        }

        $count = 0;
        $expiresAt = $now + $ttl;

        try {
            if (!flock($fp, LOCK_EX)) {
                fclose($fp);
                return Response::error('Too Many Requests', 429, [
                    'throttle' => ['Rate limiter fallback lock unavailable'],
                ]);
            }

            $raw = stream_get_contents($fp);
            if (is_string($raw) && $raw !== '') {
                $decoded = json_decode($raw, true);
                if (is_array($decoded)) {
                    $storedExpires = (int) ($decoded['expires_at'] ?? 0);
                    $storedCount = (int) ($decoded['count'] ?? 0);
                    if ($storedExpires > $now) {
                        $expiresAt = $storedExpires;
                        $count = $storedCount;
                    }
                }
            }

            $count++;
            $remainingTtl = max(0, $expiresAt - $now);

            ftruncate($fp, 0);
            rewind($fp);
            fwrite($fp, (string) json_encode([
                'count' => $count,
                'expires_at' => $expiresAt,
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            fflush($fp);
            flock($fp, LOCK_UN);
            fclose($fp);

            $remaining = max(0, $limit - $count);
            header('X-RateLimit-Limit: ' . $limit);
            header('X-RateLimit-Remaining: ' . $remaining);
            header('X-RateLimit-Reset: ' . ($now + $remainingTtl));

            if ($count > $limit) {
                if ($remainingTtl > 0) {
                    header('Retry-After: ' . $remainingTtl);
                }

                return Response::error('Too Many Requests', 429, [
                    'throttle' => [sprintf('Rate limit exceeded. Max %d requests per %d minute(s).', $limit, $windowMinutes)],
                ]);
            }
        } catch (Throwable) {
            @flock($fp, LOCK_UN);
            @fclose($fp);
            return Response::error('Too Many Requests', 429, [
                'throttle' => ['Rate limiter fallback processing failed'],
            ]);
        }

        return $next($request);
    }

    private function redis(): ?\Redis
    {
        if ($this->resolved) {
            return $this->redis;
        }

        $this->resolved = true;

        if (!class_exists(\Redis::class)) {
            return null;
        }

        try {
            $redis = new \Redis();
            $connected = $redis->connect(
                (string) Env::get('REDIS_HOST', '127.0.0.1'),
                (int) Env::get('REDIS_PORT', '6379'),
                (float) Env::get('REDIS_TIMEOUT', '0.2')
            );

            if (!$connected) {
                return null;
            }

            $password = (string) Env::get('REDIS_PASSWORD', '');
            if ($password !== '') {
                $redis->auth($password);
            }

            $database = (int) Env::get('REDIS_DB', '0');
            if ($database > 0) {
                $redis->select($database);
            }

            $this->redis = $redis;
            return $this->redis;
        } catch (Throwable) {
            return null;
        }
    }
}
