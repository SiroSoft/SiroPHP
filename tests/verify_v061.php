<?php

declare(strict_types=1);

/**
 * v0.7.1 verification suite (keeps backward-compatible filename).
 */

$basePath = dirname(__DIR__);
$baseUrl = rtrim((string) getenv('VERIFY_BASE_URL') ?: 'http://localhost:8080', '/');

$requiredPaths = [
    'public/index.php',
    'routes/api.php',
    'core/App.php',
    'benchmark/k6.js',
    'benchmark/wrk.sh',
    'benchmark/compare.md',
    '.github/workflows/test.yml',
    'core/composer.json',
    'composer.json',
    'README.md',
    '.env.example',
    'storage/logs',
];

$missing = [];
foreach ($requiredPaths as $path) {
    if (!file_exists($basePath . DIRECTORY_SEPARATOR . $path)) {
        $missing[] = $path;
    }
}

if ($missing !== []) {
    fwrite(STDERR, "Missing required paths:\n - " . implode("\n - ", $missing) . "\n");
    exit(1);
}

/**
 * @return array{status:int,body:string,json:array<string,mixed>|null,headers:array<int,string>}
 */
function httpRequest(string $method, string $url, ?array $payload = null, array $headers = []): array
{
    $requestHeaders = [
        'Accept: application/json',
    ];

    $content = '';
    if ($payload !== null) {
        $encoded = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $content = $encoded === false ? '{}' : $encoded;
        $requestHeaders[] = 'Content-Type: application/json';
    }

    foreach ($headers as $name => $value) {
        $requestHeaders[] = $name . ': ' . $value;
    }

    $context = stream_context_create([
        'http' => [
            'method' => $method,
            'header' => implode("\r\n", $requestHeaders),
            'content' => $content,
            'ignore_errors' => true,
            'timeout' => 6,
        ],
    ]);

    $body = (string) @file_get_contents($url, false, $context);
    $responseHeaders = $http_response_header ?? [];
    $statusLine = (string) ($responseHeaders[0] ?? 'HTTP/1.1 0');
    preg_match('/\s(\d{3})\s?/', $statusLine, $matches);
    $status = (int) ($matches[1] ?? 0);

    $json = json_decode($body, true);
    if (!is_array($json)) {
        $json = null;
    }

    return [
        'status' => $status,
        'body' => $body,
        'json' => $json,
        'headers' => $responseHeaders,
    ];
}

$results = [];

$record = static function (string $name, bool $passed, string $detail = '') use (&$results): void {
    $results[] = ['name' => $name, 'passed' => $passed, 'detail' => $detail];
};

// 1) Runtime health endpoint
$root = httpRequest('GET', $baseUrl . '/');
$record('runtime: GET / returns 200', $root['status'] === 200, 'status=' . $root['status']);

// 2) Register + login + protected route
$email = 'verify_' . time() . '_' . random_int(1000, 9999) . '@example.com';
$password = 'secret123';

$register = httpRequest('POST', $baseUrl . '/auth/register', [
    'name' => 'Verify User',
    'email' => $email,
    'password' => $password,
]);

$record('auth: register', in_array($register['status'], [200, 201], true), 'status=' . $register['status']);

$login = httpRequest('POST', $baseUrl . '/auth/login', [
    'email' => $email,
    'password' => $password,
]);

$token = (string) ($login['json']['data']['token'] ?? '');
$record('auth: login', $login['status'] === 200 && $token !== '', 'status=' . $login['status']);

$me = httpRequest('GET', $baseUrl . '/auth/me', null, [
    'Authorization' => 'Bearer ' . $token,
]);
$record('auth: protected route /auth/me', $me['status'] === 200, 'status=' . $me['status']);

// 3) Cache miss/hit behavior
$cacheKey = 'verifycache_' . random_int(100000, 999999);
$usersMiss = httpRequest('GET', $baseUrl . '/users?per_page=5&bench=' . $cacheKey);
$usersHit = httpRequest('GET', $baseUrl . '/users?per_page=5&bench=' . $cacheKey);
$missState = (string) ($usersMiss['json']['debug']['cache'] ?? 'N/A');
$hitState = (string) ($usersHit['json']['debug']['cache'] ?? 'N/A');
$cacheOk = $usersMiss['status'] === 200
    && $usersHit['status'] === 200
    && $missState === 'MISS'
    && $hitState === 'HIT';
$record('cache: MISS then HIT on /users', $cacheOk, 'first=' . $missState . ', second=' . $hitState);

// 4) Rate limit enforcement
$rateLimited = false;
for ($i = 0; $i < 40; $i++) {
    $res = httpRequest('POST', $baseUrl . '/auth/register', [
        'name' => 'Throttle User',
        'email' => 'throttle@example.com',
        'password' => 'secret123',
    ]);

    if ($res['status'] === 429) {
        $rateLimited = true;
        break;
    }
}
$record('throttle: returns 429 when exceeding limit', $rateLimited);

// 5) Migration status correctness
$statusOutput = [];
$statusCode = 1;
$phpBinary = PHP_BINARY !== '' ? PHP_BINARY : 'php';
$command = escapeshellarg($phpBinary) . ' ' . escapeshellarg($basePath . DIRECTORY_SEPARATOR . 'siro') . ' migrate:status 2>&1';
exec($command, $statusOutput, $statusCode);
$statusText = implode("\n", $statusOutput);
$hasAppliedLine = str_contains($statusText, 'Applied:');
$hasPendingLine = str_contains($statusText, 'Pending:');
$hasAppliedMigration = preg_match('/Applied:\s+([1-9][0-9]*)/', $statusText) === 1;
$record(
    'migrate:status reports applied/pending correctly',
    $statusCode === 0 && $hasAppliedLine && $hasPendingLine && $hasAppliedMigration,
    'exit=' . $statusCode
);

$passed = 0;
$failed = 0;

echo "=== SIRO VERIFY v0.7.1 ===\n";
foreach ($results as $result) {
    if ($result['passed']) {
        $passed++;
        echo "[PASS] {$result['name']}";
    } else {
        $failed++;
        echo "[FAIL] {$result['name']}";
    }

    $detail = trim((string) $result['detail']);
    if ($detail !== '') {
        echo " ({$detail})";
    }
    echo "\n";
}

echo "---\n";
echo "PASS: {$passed}\n";
echo "FAIL: {$failed}\n";

exit($failed === 0 ? 0 : 1);
