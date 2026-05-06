<?php
/**
 * Real dev simulation: file upload, multi-lang, CRUD
 * Run: php test_devflow.php
 */

$base = 'http://localhost:8089';
$pass = 0; $fail = 0;

function test(string $name, string $method, string $path, mixed $body = null, array $headers = []): array {
    global $base;
    $ch = curl_init($base . $path);
    $curlHeaders = ['Accept: application/json'];
    $isMultipart = false;

    if ($body !== null) {
        if (is_array($body) && !isset($headers['Content-Type'])) {
            $json = json_encode($body);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
            $curlHeaders[] = 'Content-Type: application/json';
        } elseif (is_string($body)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        } elseif (is_array($body) && isset($headers['Content-Type']) && $headers['Content-Type'] === 'multipart/form-data') {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
            $isMultipart = true;
        }
    }

    curl_setopt_array($ch, [
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_HTTPHEADER => array_merge($curlHeaders, $headers),
    ]);
    $response = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    return ['status' => $status, 'body' => json_decode((string) $response, true) ?? [], 'raw' => $response, 'error' => $error];
}

function check(string $name, bool $ok): void {
    global $pass, $fail;
    if ($ok) { $pass++; echo "  PASS: {$name}\n"; }
    else { $fail++; echo "  FAIL: {$name}\n"; }
}

echo "===== DEV SIMULATION: Real API Workflow =====\n\n";

echo "--- 1. Multi-language Profile ---\n";
$r = test('Profile EN', 'GET', '/api/profile?locale=en&name=John');
check('profile EN returns 200', $r['status'] === 200);
check('profile EN greeting has Hello', str_contains($r['body']['message'] ?? '', 'Hello'));
check('profile EN locale is en', ($r['body']['data']['locale'] ?? '') === 'en');

$r = test('Profile VI', 'GET', '/api/profile?locale=vi&name=Nam');
check('profile VI returns 200', $r['status'] === 200);
check('profile VI greeting has Xin chào', str_contains($r['body']['message'] ?? '', 'Xin chào'));

echo "\n--- 2. Posts CRUD ---\n";
$r = test('List posts', 'GET', '/api/posts');
check('list posts returns 200', $r['status'] === 200);

$r = test('Create post', 'POST', '/api/posts', [
    'title' => 'Siro Framework Introduction',
    'body' => 'SiroPHP is a zero-dependency PHP micro-framework for building fast APIs with an amazing developer experience.',
    'locale' => 'en',
    'status' => 'published',
]);
check('create post returns 201', $r['status'] === 201, $r['status'] . ': ' . ($r['body']['message'] ?? ''));
check('create post has id', isset($r['body']['data']['id']));
check('create post title matches', ($r['body']['data']['title'] ?? '') === 'Siro Framework Introduction');
$postId = $r['body']['data']['id'] ?? 0;

$r = test('Create post VI', 'POST', '/api/posts', [
    'title' => 'Giới thiệu Siro Framework',
    'body' => 'SiroPHP là một micro-framework PHP không phụ thuộc vào bất kỳ thư viện nào, giúp xây dựng API nhanh chóng.',
    'locale' => 'vi',
    'status' => 'published',
]);
check('create post VI returns 201', $r['status'] === 201);
check('create post VI has vi locale', ($r['body']['data']['locale'] ?? '') === 'vi');

$r = test('Get post by ID', 'GET', "/api/posts/{$postId}");
check('get post returns 200', $r['status'] === 200);
check('get post id matches', ($r['body']['data']['id'] ?? 0) === $postId);

$r = test('Update post', 'PUT', "/api/posts/{$postId}", ['title' => 'Siro Framework v2 Introduction']);
check('update post returns 200', $r['status'] === 200);
check('update post title changed', ($r['body']['data']['title'] ?? '') === 'Siro Framework v2 Introduction');

$r = test('Validation error', 'POST', '/api/posts', ['title' => 'Ab']);
check('validation returns 422', $r['status'] === 422);
check('validation has errors', isset($r['body']['errors']));

$r = test('Filter by locale', 'GET', '/api/posts?locale=vi');
check('filter by locale returns 200', $r['status'] === 200);
check('filter has data', is_array($r['body']['data'] ?? null));

$r = test('404', 'GET', '/api/posts/99999');
check('not found returns 404', $r['status'] === 404);

echo "\n--- 3. File Upload (create a test file first) ---\n";
$tmpFile = tempnam(sys_get_temp_dir(), 'avatar') . '.png';
file_put_contents($tmpFile, 'fake-png-content-for-testing');
$ch = curl_init($base . '/api/upload/avatar');
curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 10,
    CURLOPT_POSTFIELDS => ['avatar' => new CURLFile($tmpFile, 'image/png', 'avatar.png')],
]);
$response = curl_exec($ch);
$status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$data = json_decode((string) $response, true) ?? [];
curl_close($ch);
unlink($tmpFile);
check('upload returns 200/201', $status === 200 || $status === 201);
check('upload has path', isset($data['data']['path']));
check('upload has mime image/png', ($data['data']['mime'] ?? '') === 'image/png');

echo "\n--- 4. Debug with why (simulate failure) ---\n";
$r = test('Bad locale', 'POST', '/api/posts', [
    'title' => 'Test Post',
    'body' => 'This is a test post body content that is long enough.',
    'locale' => 'fr',
]);
check('bad locale returns 422', $r['status'] === 422);
check('locale error present', isset($r['body']['errors']['locale']));

echo "\n--- 5. Delete ---\n";
$r = test('Delete post', 'DELETE', "/api/posts/{$postId}");
check('delete returns 200', $r['status'] === 200);

$r = test('Confirm deleted', 'GET', "/api/posts/{$postId}");
check('confirm deleted returns 404', $r['status'] === 404);

echo "\n===== RESULTS =====\n";
echo "Passed: {$pass}\nFailed: {$fail}\n";
echo ($fail === 0 ? "ALL TESTS PASSED!" : "SOME TESTS FAILED!") . "\n";
exit($fail > 0 ? 1 : 0);
