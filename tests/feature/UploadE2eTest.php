<?php

declare(strict_types=1);

namespace App\Tests\Feature;

use PHPUnit\Framework\TestCase;

/**
 * True end-to-end upload: boots `php siro serve`, registers a user, sends a
 * real multipart POST to /api/upload, then fetches the returned public URL.
 *
 * Covers the whole chain UploadedFile::store() → storage/public → /storage
 * symlink serving that unit tests cannot reach (is_uploaded_file() requires
 * a real HTTP upload). Skipped when ext-curl is missing or the port is busy.
 */
final class UploadE2eTest extends TestCase
{
    private const PORT = 8099;

    private static mixed $server = null;

    /** @var resource|null */
    private static $pipes = null;

    private static string $tmpPng = '';

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        if (!extension_loaded('curl')) {
            self::markTestSkipped('ext-curl is required for the upload e2e test.');
        }

        $base = dirname(__DIR__, 2);
        self::$tmpPng = sys_get_temp_dir() . '/siro-upload-e2e.png';
        $png = base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg=='
        );
        file_put_contents(self::$tmpPng, (string) $png);

        $probe = @fsockopen('127.0.0.1', self::PORT);
        if (is_resource($probe)) {
            fclose($probe);
            self::markTestSkipped('Port ' . self::PORT . ' is busy; cannot boot e2e server.');
        }

        $descriptors = [0 => ['file', 'NUL', 'r'], 1 => ['file', 'NUL', 'w'], 2 => ['file', 'NUL', 'w']];
        // Plain php -S with the framework router (same stack manual QA uses).
        // TCP connect = ready (never blocks on app-level health checks).
        $cmd = 'php -S 127.0.0.1:' . self::PORT
            . ' -t ' . escapeshellarg($base . '/public')
            . ' ' . escapeshellarg($base . '/public/router.php');
        $proc = proc_open($cmd, $descriptors, $pipes, $base);
        if (!is_resource($proc)) {
            self::markTestSkipped('Could not boot php -S.');
        }
        self::$server = $proc;

        $ready = false;
        for ($i = 0; $i < 40; $i++) {
            $status = proc_get_status($proc);
            if (!($status['running'] ?? false)) {
                break;
            }
            $sock = @fsockopen('127.0.0.1', self::PORT, $errno, $errstr, 1);
            if (is_resource($sock)) {
                fclose($sock);
                $ready = true;
                break;
            }
            usleep(250000);
        }
        if (!$ready) {
            self::markTestSkipped('E2E server did not become ready.');
        }
    }

    public static function tearDownAfterClass(): void
    {
        if (is_resource(self::$server)) {
            proc_terminate(self::$server, 9);
            proc_close(self::$server);
        }
        self::$server = null;
        if (self::$tmpPng !== '' && is_file(self::$tmpPng)) {
            @unlink(self::$tmpPng);
        }
        parent::tearDownAfterClass();
    }

    /**
     * @param array<string,string> $headers
     * @return array{code:int,body:string}|null
     */
    private static function curl(string $method, string $path, array $headers = [], array $postFields = []): ?array
    {
        $ch = curl_init('http://127.0.0.1:' . self::PORT . $path);
        if ($ch === false) {
            return null;
        }
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        if ($headers !== []) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }
        if ($postFields !== []) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
        }
        $body = curl_exec($ch);
        if ($body === false) {
            curl_close($ch);
            return null;
        }
        $code = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        curl_close($ch);
        return ['code' => $code, 'body' => (string) $body];
    }

    private static function authToken(): string
    {
        $email = 'uploader-' . bin2hex(random_bytes(4)) . '@e2e.test';
        // Register via JSON body (curl array would send multipart; use JSON string).
        $ch = curl_init('http://127.0.0.1:' . self::PORT . '/api/auth/register');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_POSTFIELDS, (string) json_encode([
            'name' => 'Uploader E2E',
            'email' => $email,
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
        ]));
        $raw = (string) curl_exec($ch);
        curl_close($ch);
        $data = json_decode($raw, true);
        self::assertIsArray($data);
        self::assertTrue((bool) ($data['success'] ?? false), 'Register failed: ' . substr($raw, 0, 200));

        $login = self::curlRawJson('/api/auth/login', ['email' => $email, 'password' => 'secret123']);
        $token = (string) ($login['data']['token'] ?? '');
        self::assertNotSame('', $token, 'Login token missing: ' . json_encode($login));
        return $token;
    }

    /** @return array<string,mixed> */
    private static function curlRawJson(string $path, array $payload): array
    {
        $ch = curl_init('http://127.0.0.1:' . self::PORT . $path);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_POSTFIELDS, (string) json_encode($payload));
        $raw = (string) curl_exec($ch);
        curl_close($ch);
        $decoded = json_decode($raw, true);
        self::assertIsArray($decoded, 'Invalid JSON from ' . $path . ': ' . substr($raw, 0, 200));
        return $decoded;
    }

    public function testUploadRequiresAuth(): void
    {
        $res = self::curl('POST', '/api/upload', [], [
            'file' => new \CURLFile(self::$tmpPng, 'image/png', 'red.png'),
        ]);
        self::assertNotNull($res);
        self::assertSame(401, $res['code']);
    }

    public function testUploadMultipartEndToEnd(): void
    {
        $token = self::authToken();

        $res = self::curl('POST', '/api/upload', ['Authorization: Bearer ' . $token], [
            'file' => new \CURLFile(self::$tmpPng, 'image/png', 'red.png'),
        ]);
        self::assertNotNull($res);
        self::assertSame(201, $res['code'], 'Upload failed: ' . substr($res['body'], 0, 300));

        $data = json_decode($res['body'], true);
        self::assertIsArray($data);
        $url = (string) ($data['data']['url'] ?? '');
        self::assertStringStartsWith('/storage/', (string) ($data['data']['path'] ?? ''));
        self::assertStringContainsString('/storage/', $url);

        // The returned public URL must actually serve the bytes.
        $path = (string) parse_url($url, PHP_URL_PATH);
        $fetch = self::curl('GET', $path !== '' ? $path : '/');
        self::assertNotNull($fetch);
        self::assertSame(200, $fetch['code'], 'Uploaded file not reachable at ' . $url);
        self::assertStringStartsWith("\x89PNG", $fetch['body']);
    }
}
