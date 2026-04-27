<?php
// Debug test failures

$baseUrl = 'http://localhost:8080';

echo "=== Test 4: Malformed JSON ===\n";
$ctx = stream_context_create([
    'http' => [
        'method' => 'POST',
        'header' => "Content-Type: application/json\r\n",
        'content' => '{invalid json}',
        'timeout' => 5,
        'ignore_errors' => true,
    ],
]);
$resp = @file_get_contents("{$baseUrl}/api/auth/login", false, $ctx);
$code = 200;
if (isset($http_response_header[0])) {
    preg_match('/HTTP\/\d\.\d\s+(\d+)/', $http_response_header[0], $matches);
    $code = isset($matches[1]) ? (int) $matches[1] : 200;
}
echo "Status: {$code}\n";
echo "Response: {$resp}\n";
echo "Parsed: ";
print_r(json_decode($resp, true));
echo "\n\n";

echo "=== Test 5: Missing required fields ===\n";
$ctx = stream_context_create([
    'http' => [
        'method' => 'POST',
        'header' => "Content-Type: application/json\r\n",
        'content' => json_encode([]),
        'timeout' => 5,
        'ignore_errors' => true,
    ],
]);
$resp = @file_get_contents("{$baseUrl}/api/auth/register", false, $ctx);
$code = 200;
if (isset($http_response_header[0])) {
    preg_match('/HTTP\/\d\.\d\s+(\d+)/', $http_response_header[0], $matches);
    $code = isset($matches[1]) ? (int) $matches[1] : 200;
}
echo "Status: {$code}\n";
echo "Response: {$resp}\n";
echo "Parsed: ";
print_r(json_decode($resp, true));
echo "\n\n";

echo "=== Test 9: Auth with token ===\n";
// First register
$ctx = stream_context_create([
    'http' => [
        'method' => 'POST',
        'header' => "Content-Type: application/json\r\n",
        'content' => json_encode(['name' => 'Test', 'email' => 'test9@example.com', 'password' => 'secret123']),
        'timeout' => 5,
        'ignore_errors' => true,
    ],
]);
$resp = @file_get_contents("{$baseUrl}/api/auth/register", false, $ctx);
$data = json_decode($resp, true);
$token = $data['data']['token'] ?? null;
echo "Token: " . substr($token ?? 'NONE', 0, 20) . "...\n";

if ($token) {
    // Try to access protected route
    $ctx = stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' => "Authorization: Bearer {$token}\r\n",
            'timeout' => 5,
            'ignore_errors' => true,
        ],
    ]);
    $resp = @file_get_contents("{$baseUrl}/api/auth/me", false, $ctx);
    $code = 200;
    if (isset($http_response_header[0])) {
        preg_match('/HTTP\/\d\.\d\s+(\d+)/', $http_response_header[0], $matches);
        $code = isset($matches[1]) ? (int) $matches[1] : 200;
    }
    echo "Status: {$code}\n";
    echo "Response: {$resp}\n";
}
echo "\n\n";

echo "=== Test 11: Logout ===\n";
if ($token) {
    $ctx = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => "Authorization: Bearer {$token}\r\n",
            'timeout' => 5,
            'ignore_errors' => true,
        ],
    ]);
    $resp = @file_get_contents("{$baseUrl}/api/auth/logout", false, $ctx);
    $code = 200;
    if (isset($http_response_header[0])) {
        preg_match('/HTTP\/\d\.\d\s+(\d+)/', $http_response_header[0], $matches);
        $code = isset($matches[1]) ? (int) $matches[1] : 200;
    }
    echo "Logout Status: {$code}\n";
    echo "Logout Response: {$resp}\n";
}
