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
     * User-facing message for a failed check, distinguishing a missing
     * token (widget blocked or not solved yet) from a rejected token.
     */
    public static function failureMessage(string $token): string
    {
        if (trim($token) === '') {
            return 'Security check did not complete. Please wait for the human-verification box and try again.';
        }
        return 'Human verification failed. Please try again.';
    }

    /**
     * @param string $token Value of cf-turnstile-response from the client
     * @param string|null $remoteIp Ignored (kept for backward compatibility).
     *   Cloudflare's remoteip is optional, and the app runs behind
     *   Cloudflare + nginx + Docker where Request::ip() resolves to a proxy
     *   address, which makes siteverify reject otherwise valid tokens.
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

        try {
            $res = Http::post(self::VERIFY_URL, $payload, ['Content-Type' => 'application/x-www-form-urlencoded']);
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
