<?php
/**
 * End-to-end test: HTTP API, CRUD, Auth, Health
 * Run: php test_e2e.php
 */

$base = 'http://localhost:8086';
$pass = 0;
$fail = 0;

function test(string $name, string $method, string $path, mixed $body = null, array $headers = []): array {
    global $base;
    $ch = curl_init($base . $path);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 5,
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_HTTPHEADER => array_merge(['Accept: application/json'], $headers),
    ]);
    if ($body !== null) {
        $json = is_string($body) ? $body : json_encode($body);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array_merge(['Content-Type: application/json'], $headers));
    }
    $response = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    $data = json_decode((string) $response, true) ?? [];
    return ['status' => $status, 'body' => $data, 'raw' => $response];
}

function check(string $name, bool $ok, string $detail = ''): void {
    global $pass, $fail;
    if ($ok) { $pass++; echo "  [PASS] {$name}\n"; }
    else { $fail++; echo "  [FAIL] {$name}" . ($detail ? ": {$detail}" : '') . "\n"; }
}

echo "===== ROUND 2: HTTP Endpoints + CRUD =====\n";

// 1. Root
$r = test('Root', 'GET', '/');
check('GET / returns 200', $r['status'] === 200);
check('GET / has success=true', ($r['body']['success'] ?? false) === true);
check('GET / has version', isset($r['body']['data']['version']));

// 2. Health
$r = test('Health', 'GET', '/health');
assert('GET /health returns 200', $r['status'] === 200);
assert('GET /health status=healthy', ($r['body']['data']['status'] ?? '') === 'healthy');
assert('GET /health db connected', ($r['body']['data']['database'] ?? '') === 'connected');

// 3. Register
$r = test('Register', 'POST', '/api/auth/register', [
    'name' => 'Test User',
    'email' => 'e2e@test.com',
    'password' => 'secret123',
]);
assert('POST /auth/register returns 201', $r['status'] === 201);
assert('Register has token', isset($r['body']['data']['token']));
assert('Register has refresh_token', isset($r['body']['data']['refresh_token']));
$token = $r['body']['data']['token'] ?? '';

// 4. Duplicate register
$r = test('Register duplicate', 'POST', '/api/auth/register', [
    'name' => 'Test User',
    'email' => 'e2e@test.com',
    'password' => 'secret123',
]);
assert('Duplicate register returns 422', $r['status'] === 422);
assert('Duplicate has email error', isset($r['body']['errors']['email']));

// 5. Login
$r = test('Login', 'POST', '/api/auth/login', [
    'email' => 'e2e@test.com',
    'password' => 'secret123',
]);
assert('POST /auth/login returns 200', $r['status'] === 200);
assert('Login has token', isset($r['body']['data']['token']));
$token = $r['body']['data']['token'] ?? '';

// 6. Auth me
$r = test('Auth me', 'GET', '/api/auth/me', null, ["Authorization: Bearer {$token}"]);
assert('GET /auth/me returns 200', $r['status'] === 200);
assert('Auth me has user id', isset($r['body']['data']['id']));
assert('Auth me has user email', ($r['body']['data']['email'] ?? '') === 'e2e@test.com');

// 7. Auth me without token
$r = test('Auth me no token', 'GET', '/api/auth/me');
assert('GET /auth/me without token returns 401', $r['status'] === 401);

// 8. List users
$r = test('List users', 'GET', '/api/users');
assert('GET /api/users returns 200', $r['status'] === 200);
assert('Users has data array', is_array($r['body']['data'] ?? null));
assert('Users has meta', isset($r['body']['data'][0]['id']));

// 9. List products
$r = test('List products', 'GET', '/api/products');
assert('GET /api/products returns 200', $r['status'] === 200);

// 10. List categories
$r = test('List categories', 'GET', '/api/categories');
assert('GET /api/categories returns 200', $r['status'] === 200);

// 11. List tags
$r = test('List tags', 'GET', '/api/tag');
assert('GET /api/tag returns 200', $r['status'] === 200);

// 12. Create product (with auth)
$r = test('Create product', 'POST', '/api/products', [
    'name' => 'E2E Product',
    'price' => 99.99,
], ["Authorization: Bearer {$token}"]);
assert('POST /api/products returns 201', $r['status'] === 201);
assert('Product has name', ($r['body']['data']['name'] ?? '') === 'E2E Product');

// 13. 404
$r = test('Not found', 'GET', '/api/nonexistent');
assert('GET /api/nonexistent returns 404', $r['status'] === 404);

// 14. Validation error
$r = test('Validation error', 'POST', '/api/auth/login', ['email' => 'bad']);
assert('Invalid login returns 422', $r['status'] === 422);
assert('Invalid login has error key', isset($r['body']['errors']['email']));

// 15. Login with wrong password
$r = test('Wrong password', 'POST', '/api/auth/login', [
    'email' => 'e2e@test.com',
    'password' => 'wrongpassword',
]);
assert('Wrong password returns 401', $r['status'] === 401);

echo "\n===== RESULTS =====\n";
echo "Passed: {$pass}\n";
echo "Failed: {$fail}\n";
echo ($fail === 0 ? "ALL TESTS PASSED!\n" : "SOME TESTS FAILED!\n");
exit($fail > 0 ? 1 : 0);
