<?php

declare(strict_types=1);

namespace App\Support;

use Siro\Core\Env;
use Siro\Core\Http;

/**
 * Cloudflare Turnstile server-side verification.
 *
 * verify() checks a client token against
 * https://challenges.cloudflare.com/turnstile/v0/siteverify.
 * Fail-open when TURNSTILE_SECRET is not configured (local dev);
 * fail-closed in production once the secret is set.
 */
final class Turnstile
{
    private const VERIFY_URL = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';

    public static function isConfigured(): bool
    {
        return trim((string) Env::get('TURNSTILE_SECRET', '')) !== '';
    }

    public static function siteKey(): string
    {
        return trim((string) Env::get('TURNSTILE_SITE_KEY', ''));
    }

    /**
     * @param string $token Value of cf-turnstile-response from the client
     * @param string|null $remoteIp Optional client IP for verification
     */
    public static function verify(string $token, ?string $remoteIp = null): bool
    {
        $secret = trim((string) Env::get('TURNSTILE_SECRET', ''));
        if ($secret === '') {
            return true;
        }
        if (trim($token) === '') {
            return false;
        }

        $payload = ['secret' => $secret, 'response' => $token];
        if ($remoteIp !== null && $remoteIp !== '') {
            $payload['remoteip'] = $remoteIp;
        }

        try {
            $res = Http::post(self::VERIFY_URL, $payload, ['Content-Type: application/x-www-form-urlencoded']);
            if (!$res->ok()) {
                return false;
            }
            $data = $res->json();
        } catch (\Throwable) {
            return false;
        }

        return ($data['success'] ?? false) === true;
    }
}
