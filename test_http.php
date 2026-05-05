<?php
$base = 'http://localhost:8080';
$token = '';
$tests = [['Root', 'GET', '/', 200, true],
    ['Health', 'GET', '/health', 200, true],
    ['Users', 'GET', '/api/users', 200, true],
    ['Products', 'GET', '/api/products', 200, true],
    ['Categories', 'GET', '/api/categories', 200, true],
    ['Tags', 'GET', '/api/tag', 200, true],
    ['404', 'GET', '/api/nonexistent', 404, true],
];

foreach ($tests as $t) {
    $ch = curl_init($base . $t[1]);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => 1, CURLOPT_TIMEOUT => 5]);
    $r = curl_exec($ch);
    $s = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    $ok = $s === $t[2];
    echo ($ok ? '  PASS' : '  FAIL') . " {$t[0]}: {$t[1]} => {$s} (expected {$t[2]})\n";
}

// Register
$ch = curl_init($base . '/api/auth/register');
curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => 1, CURLOPT_TIMEOUT => 5, CURLOPT_POST => 1, CURLOPT_POSTFIELDS => '{"name":"E2E","email":"e2e@test.com","password":"secret123"}', CURLOPT_HTTPHEADER => ['Content-Type: application/json']]);
$r = curl_exec($ch);
$s = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$j = json_decode($r, true);
$token = $j['data']['token'] ?? '';
echo ($s === 201 ? '  PASS' : '  FAIL') . " Register: POST /auth/register => {$s} token=" . (isset($j['data']['token']) ? 'yes' : 'no') . "\n";

// Duplicate register
$ch = curl_init($base . '/api/auth/register');
curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => 1, CURLOPT_TIMEOUT => 5, CURLOPT_POST => 1, CURLOPT_POSTFIELDS => '{"name":"E2E","email":"e2e@test.com","password":"secret123"}', CURLOPT_HTTPHEADER => ['Content-Type: application/json']]);
$r = curl_exec($ch);
$s = curl_getinfo($ch, CURLINFO_HTTP_CODE);
echo ($s === 422 ? '  PASS' : '  FAIL') . " Duplicate register: => {$s} (expected 422)\n";

// Login
$ch = curl_init($base . '/api/auth/login');
curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => 1, CURLOPT_TIMEOUT => 5, CURLOPT_POST => 1, CURLOPT_POSTFIELDS => '{"email":"e2e@test.com","password":"secret123"}', CURLOPT_HTTPHEADER => ['Content-Type: application/json']]);
$r = curl_exec($ch);
$s = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$j = json_decode($r, true);
$token = $j['data']['token'] ?? '';
echo ($s === 200 && $token !== '' ? '  PASS' : '  FAIL') . " Login: => {$s} token=" . ($token !== '' ? 'yes' : 'no') . "\n";

// Auth me with token
$ch = curl_init($base . '/api/auth/me');
curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => 1, CURLOPT_TIMEOUT => 5, CURLOPT_HTTPHEADER => ["Authorization: Bearer $token"]]);
$r = curl_exec($ch);
$s = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$j = json_decode($r, true);
echo ($s === 200 && ($j['data']['email'] ?? '') === 'e2e@test.com' ? '  PASS' : '  FAIL') . " Auth me: => {$s} email=" . ($j['data']['email'] ?? 'none') . "\n";

// Auth me without token
$ch = curl_init($base . '/api/auth/me');
curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => 1, CURLOPT_TIMEOUT => 5]);
$r = curl_exec($ch);
$s = curl_getinfo($ch, CURLINFO_HTTP_CODE);
echo ($s === 401 ? '  PASS' : '  FAIL') . " Auth me (no token): => {$s} (expected 401)\n";

// Wrong password
$ch = curl_init($base . '/api/auth/login');
curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => 1, CURLOPT_TIMEOUT => 5, CURLOPT_POST => 1, CURLOPT_POSTFIELDS => '{"email":"e2e@test.com","password":"wrong"}', CURLOPT_HTTPHEADER => ['Content-Type: application/json']]);
$r = curl_exec($ch);
$s = curl_getinfo($ch, CURLINFO_HTTP_CODE);
echo ($s === 401 ? '  PASS' : '  FAIL') . " Wrong password: => {$s} (expected 401)\n";

// Create product with auth
if ($token) {
    $ch = curl_init($base . '/api/products');
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => 1, CURLOPT_TIMEOUT => 5, CURLOPT_POST => 1, CURLOPT_POSTFIELDS => '{"name":"Test Product","price":49.99}', CURLOPT_HTTPHEADER => ['Content-Type: application/json', "Authorization: Bearer $token"]]);
    $r = curl_exec($ch);
    $s = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $j = json_decode($r, true);
    echo ($s === 201 && ($j['data']['name'] ?? '') === 'Test Product' ? '  PASS' : '  FAIL') . " Create product: => {$s} name=" . ($j['data']['name'] ?? 'none') . "\n";
}

echo "\n=== HTTP TEST COMPLETE ===\n";
