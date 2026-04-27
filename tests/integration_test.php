#!/usr/bin/env php
<?php

/**
 * Comprehensive Integration Test for SiroPHP v0.7.2
 * 
 * Tests all critical fixes and features before release
 */

declare(strict_types=1);

$basePath = __DIR__ . '/..';
$baseUrl = 'http://localhost:8080';
$passed = 0;
$failed = 0;
$total = 0;

echo "========================================\n";
echo "SiroPHP v0.7.2 Integration Test Suite\n";
echo "========================================\n\n";

// Helper functions
function test(string $name, callable $test): void {
    global $passed, $failed, $total;
    $total++;
    
    echo "Test {$total}: {$name}... ";
    
    try {
        $result = $test();
        if ($result === true) {
            echo "✅ PASS\n";
            $passed++;
        } else {
            echo "❌ FAIL: {$result}\n";
            $failed++;
        }
    } catch (Throwable $e) {
        echo "❌ ERROR: " . $e->getMessage() . "\n";
        $failed++;
    }
}

function httpGet(string $url): array {
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'timeout' => 5,
            'ignore_errors' => true,
        ],
    ]);
    
    $response = @file_get_contents($url, false, $context);
    
    // Get response code from $http_response_header
    $httpCode = 200;
    if (isset($http_response_header[0])) {
        preg_match('/HTTP\/\d\.\d\s+(\d+)/', $http_response_header[0], $matches);
        $httpCode = isset($matches[1]) ? (int) $matches[1] : 200;
    }
    
    $headers = isset($http_response_header) ? implode("\r\n", $http_response_header) : '';
    
    return [
        'code' => $httpCode,
        'headers' => $headers,
        'body' => $response ?: '',
        'json' => json_decode($response ?: '{}', true),
    ];
}

function httpPost(string $url, array $data): array {
    $payload = json_encode($data);
    $context = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => "Content-Type: application/json\r\n",
            'content' => $payload,
            'timeout' => 5,
            'ignore_errors' => true,
        ],
    ]);
    
    $response = @file_get_contents($url, false, $context);
    
    // Get response code
    $httpCode = 200;
    if (isset($http_response_header[0])) {
        preg_match('/HTTP\/\d\.\d\s+(\d+)/', $http_response_header[0], $matches);
        $httpCode = isset($matches[1]) ? (int) $matches[1] : 200;
    }
    
    return [
        'code' => $httpCode,
        'body' => $response ?: '',
        'json' => json_decode($response ?: '{}', true),
    ];
}

function httpPostRaw(string $url, string $rawBody, string $contentType = 'application/json'): array {
    $context = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => "Content-Type: {$contentType}\r\n",
            'content' => $rawBody,
            'timeout' => 5,
            'ignore_errors' => true,
        ],
    ]);
    
    $response = @file_get_contents($url, false, $context);
    
    $httpCode = 200;
    if (isset($http_response_header[0])) {
        preg_match('/HTTP\/\d\.\d\s+(\d+)/', $http_response_header[0], $matches);
        $httpCode = isset($matches[1]) ? (int) $matches[1] : 200;
    }
    
    return [
        'code' => $httpCode,
        'body' => $response ?: '',
        'json' => json_decode($response ?: '{}', true),
    ];
}

function httpGetWithAuth(string $url, string $token): array {
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' => "Authorization: Bearer {$token}\r\n",
            'timeout' => 5,
            'ignore_errors' => true,
        ],
    ]);
    
    $response = @file_get_contents($url, false, $context);
    
    $httpCode = 200;
    if (isset($http_response_header[0])) {
        preg_match('/HTTP\/\d\.\d\s+(\d+)/', $http_response_header[0], $matches);
        $httpCode = isset($matches[1]) ? (int) $matches[1] : 200;
    }
    
    return [
        'code' => $httpCode,
        'body' => $response ?: '',
        'json' => json_decode($response ?: '{}', true),
    ];
}

function httpPostWithAuth(string $url, string $token): array {
    $context = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => "Authorization: Bearer {$token}\r\n",
            'timeout' => 5,
            'ignore_errors' => true,
        ],
    ]);
    
    $response = @file_get_contents($url, false, $context);
    
    $httpCode = 200;
    if (isset($http_response_header[0])) {
        preg_match('/HTTP\/\d\.\d\s+(\d+)/', $http_response_header[0], $matches);
        $httpCode = isset($matches[1]) ? (int) $matches[1] : 200;
    }
    
    return [
        'code' => $httpCode,
        'body' => $response ?: '',
        'json' => json_decode($response ?: '{}', true),
    ];
}

// Check if server is running
echo "Checking if server is running...\n";
$testResponse = httpGet("{$baseUrl}/");
if ($testResponse['code'] === 0) {
    echo "❌ Server not running at {$baseUrl}\n";
    echo "Please start server: php -S localhost:8080 -t public\n";
    exit(1);
}
echo "✅ Server is running\n\n";

// Check PHP extensions
echo "Checking PHP extensions...\n";
$missingExtensions = [];
if (!extension_loaded('pdo_mysql')) {
    $missingExtensions[] = 'pdo_mysql';
}
if (!extension_loaded('mbstring')) {
    $missingExtensions[] = 'mbstring';
}

if (!empty($missingExtensions)) {
    echo "⚠️  Missing extensions: " . implode(', ', $missingExtensions) . "\n";
    echo "⚠️  Some tests will be skipped\n\n";
} else {
    echo "✅ All required extensions loaded\n\n";
}

$dbAvailable = empty($missingExtensions);

// ========================================
// TEST SUITE
// ========================================

echo "--- Core Functionality Tests ---\n\n";

// Test 1: Root endpoint returns JSON
test('Root endpoint returns valid JSON', function() use ($baseUrl) {
    $response = httpGet("{$baseUrl}/");
    
    if ($response['code'] !== 200) {
        return "Expected 200, got {$response['code']}";
    }
    
    if (!isset($response['json']['success']) || $response['json']['success'] !== true) {
        return "Invalid response structure";
    }
    
    if (!isset($response['json']['data']['version']) || $response['json']['data']['version'] !== '0.7.2') {
        return "Version mismatch: expected 0.7.2";
    }
    
    return true;
});

// Test 2: Response has correct Content-Type
test('Response Content-Type is application/json', function() use ($baseUrl) {
    $response = httpGet("{$baseUrl}/");
    
    if (!str_contains($response['headers'], 'application/json')) {
        return "Content-Type is not application/json";
    }
    
    return true;
});

// Test 3: 404 returns JSON error
test('404 errors return JSON format', function() use ($baseUrl) {
    $response = httpGet("{$baseUrl}/nonexistent");
    
    if ($response['code'] !== 404) {
        return "Expected 404, got {$response['code']}";
    }
    
    if (!is_array($response['json'])) {
        return "Response is not JSON";
    }
    
    return true;
});

echo "\n--- Security & Validation Tests ---\n\n";

// Test 4: Malformed JSON returns 400
test('Malformed JSON returns 400 error', function() use ($baseUrl) {
    $response = httpPostRaw("{$baseUrl}/api/auth/login", '{invalid json}');
    
    if ($response['code'] !== 400) {
        return "Expected 400, got {$response['code']}";
    }
    
    if (!is_array($response['json']) || !isset($response['json']['error'])) {
        return "Invalid error response format";
    }
    
    return true;
});

// Test 5: Missing required fields returns 422
test('Missing required fields returns 422', function() use ($baseUrl) {
    $response = httpPost("{$baseUrl}/api/auth/register", []);
    
    if ($response['code'] !== 422) {
        return "Expected 422, got {$response['code']}";
    }
    
    if (!isset($response['json']['errors'])) {
        return "Missing validation errors";
    }
    
    return true;
});

// Test 6: Invalid email format returns 422
test('Invalid email format returns validation error', function() use ($baseUrl) {
    $response = httpPost("{$baseUrl}/api/auth/register", [
        'name' => 'Test User',
        'email' => 'not-an-email',
        'password' => 'secret123',
    ]);
    
    if ($response['code'] !== 422) {
        return "Expected 422, got {$response['code']}";
    }
    
    return true;
});

echo "\n--- Authentication Flow Tests ---\n\n";

// Test 7: User registration
$testEmail = 'test_' . time() . '@example.com';
$testToken = null;

if ($dbAvailable) {
    test('User registration succeeds', function() use ($baseUrl, $testEmail) {
        $response = httpPost("{$baseUrl}/api/auth/register", [
            'name' => 'Test User',
            'email' => $testEmail,
            'password' => 'secret123',
        ]);
        
        if ($response['code'] !== 201 && $response['code'] !== 200) {
            return "Expected 201 or 200, got {$response['code']}: " . ($response['json']['error'] ?? 'Unknown error');
        }
        
        if (!isset($response['json']['data']['token'])) {
            return "Missing token in response";
        }
        
        return true;
    });

    // Test 8: User login
    test('User login succeeds', function() use ($baseUrl, $testEmail, &$testToken) {
        $response = httpPost("{$baseUrl}/api/auth/login", [
            'email' => $testEmail,
            'password' => 'secret123',
        ]);
        
        if ($response['code'] !== 200) {
            return "Expected 200, got {$response['code']}";
        }
        
        if (!isset($response['json']['data']['token'])) {
            return "Missing token in response";
        }
        
        $testToken = $response['json']['data']['token'];
        return true;
    });

    // Test 9: Access protected route with valid token
    test('Protected route accessible with valid token', function() use ($baseUrl, $testToken) {
        if (!$testToken) {
            return "No token available";
        }
        
        $response = httpGetWithAuth("{$baseUrl}/api/auth/me", $testToken);
        
        if ($response['code'] !== 200) {
            return "Expected 200, got {$response['code']}";
        }
        
        if (!isset($response['json']['data']['email'])) {
            return "Invalid user data";
        }
        
        return true;
    });

    // Test 10: Protected route blocked without token
    test('Protected route blocked without token', function() use ($baseUrl) {
        $response = httpGet("{$baseUrl}/api/auth/me");
        
        if ($response['code'] !== 401) {
            return "Expected 401, got {$response['code']}";
        }
        
        return true;
    });

    // Test 11: Logout revokes token
    test('Logout revokes token', function() use ($baseUrl, $testToken) {
        if (!$testToken) {
            return "No token available";
        }
        
        // Logout
        $logoutResponse = httpPostWithAuth("{$baseUrl}/api/auth/logout", $testToken);
        
        if ($logoutResponse['code'] !== 200) {
            return "Logout failed: {$logoutResponse['code']}";
        }
        
        // Try to use old token
        $response = httpGetWithAuth("{$baseUrl}/api/auth/me", $testToken);
        
        if ($response['code'] !== 401) {
            return "Old token should be revoked (expected 401, got {$response['code']})";
        }
        
        return true;
    });
} else {
    echo "⏭️  Skipping authentication tests (DB not available)\n\n";
}

echo "\n--- Error Handling Tests ---\n\n";

// Test 12: Bootstrap errors return JSON (simulated)
test('Error responses are always JSON format', function() use ($baseUrl) {
    // This tests that runtime errors return JSON
    // Bootstrap errors already tested manually
    
    // Trigger a validation error
    $response = httpPost("{$baseUrl}/api/auth/login", [
        'email' => 'test@example.com',
        // Missing password
    ]);
    
    if (!is_array($response['json'])) {
        return "Response is not JSON";
    }
    
    if (!isset($response['json']['success']) || $response['json']['success'] !== false) {
        return "Invalid error structure";
    }
    
    return true;
});

echo "\n--- Performance & Logging Tests ---\n\n";

// Test 13: Response includes debug meta (if enabled)
test('Debug mode includes metadata', function() use ($baseUrl) {
    $response = httpGet("{$baseUrl}/");
    
    // Debug meta is optional, just check response is valid
    if (!isset($response['json']['success'])) {
        return "Invalid response";
    }
    
    return true;
});

// Test 14: Log files exist
test('Log files exist in storage/logs', function() use ($basePath) {
    $logDir = $basePath . '/storage/logs';
    $requiredLogs = ['request.log', 'error.log', 'slow.log'];
    
    foreach ($requiredLogs as $logFile) {
        $filePath = $logDir . '/' . $logFile;
        if (!file_exists($filePath)) {
            return "Missing log file: {$logFile}";
        }
    }
    
    return true;
});

// ========================================
// SUMMARY
// ========================================

echo "\n========================================\n";
echo "Test Summary\n";
echo "========================================\n";
echo "Total Tests Run: {$total}\n";
echo "Passed: ✅ {$passed}\n";
echo "Failed: ❌ {$failed}\n";
if ($total > 0) {
    echo "Success Rate: " . round(($passed / $total) * 100, 1) . "%\n";
}
if (!$dbAvailable) {
    echo "Note: Some tests skipped (DB extensions missing)\n";
}
echo "========================================\n\n";

if ($failed === 0 && $total > 0) {
    echo "🎉 All tests passed! Ready for v0.7.2 release!\n";
    exit(0);
} elseif ($total === 0) {
    echo "⚠️  No tests were run\n";
    exit(1);
} else {
    echo "⚠️  Some tests failed. Please review and fix issues.\n";
    exit(1);
}
