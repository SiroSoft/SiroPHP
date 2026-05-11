<?php

declare(strict_types=1);

/**
 * Comprehensive Functional Test Script for SiroPHP
 * Tests ALL 22 scenarios including auth, CRUD, filtering, CORS, security headers.
 *
 * Usage: php tests/comprehensive_functional_test.php
 */

define('BASE_PATH', dirname(__DIR__));
require_once BASE_PATH . '/vendor/autoload.php';

spl_autoload_register(function (string $class): void {
    $prefix = 'App\\Tests\\';
    if (strncmp($class, $prefix, strlen($prefix)) !== 0) return;
    $relativeClass = substr($class, strlen($prefix));
    $file = BASE_PATH . '/tests/' . str_replace('\\', DIRECTORY_SEPARATOR, $relativeClass) . '.php';
    if (file_exists($file)) require $file;
});

date_default_timezone_set('UTC');
error_reporting(E_ALL);
ini_set('display_errors', '1');

use Siro\Core\App;
use Siro\Core\Database;
use Siro\Core\Request;
use Siro\Core\Response;
use Siro\Core\Router;
use Siro\Core\ValidationException;

$passed = 0;
$failed = 0;
$results = [];

function test(string $name, callable $fn): void {
    global $passed, $failed, $results;
    try {
        $fn();
        $passed++;
        $results[] = "  PASS: {$name}";
    } catch (\Throwable $e) {
        $failed++;
        $results[] = "  FAIL: {$name}";
        $results[] = "    " . $e->getMessage();
    }
}

function assertEq(mixed $expected, mixed $actual, string $msg = ''): void {
    if ($expected !== $actual) {
        throw new RuntimeException($msg ?: sprintf("Expected %s, got %s", var_export($expected, true), var_export($actual, true)));
    }
}

function assertTrue(mixed $val, string $msg = ''): void {
    if ($val !== true) {
        throw new RuntimeException($msg ?: sprintf("Expected true, got %s", var_export($val, true)));
    }
}

function assertFalse(mixed $val, string $msg = ''): void {
    if ($val !== false) {
        throw new RuntimeException($msg ?: sprintf("Expected false, got %s", var_export($val, true)));
    }
}

function assertArrayHasKey(string $key, array $arr, string $msg = ''): void {
    if (!array_key_exists($key, $arr)) {
        throw new RuntimeException($msg ?: "Array missing key '$key', keys: " . implode(', ', array_keys($arr)));
    }
}

function assertArrayNotHasKey(string $key, array $arr, string $msg = ''): void {
    if (array_key_exists($key, $arr)) {
        throw new RuntimeException($msg ?: "Array unexpectedly has key '$key'");
    }
}

function assertContains(string $needle, string $haystack, string $msg = ''): void {
    if (!str_contains($haystack, $needle)) {
        throw new RuntimeException($msg ?: "Expected '$needle' to be in string, got: " . substr($haystack, 0, 200));
    }
}

function assertInArray(mixed $needle, array $haystack, string $msg = ''): void {
    if (!in_array($needle, $haystack, true)) {
        throw new RuntimeException($msg ?: sprintf("Expected one of [%s], got %s", implode(',', $haystack), var_export($needle, true)));
    }
}

function assertGreaterThan(int $min, mixed $val, string $msg = ''): void {
    if ($val <= $min) {
        throw new RuntimeException($msg ?: "Expected value > $min, got $val");
    }
}

// ----- App Helpers -----
$appPool = [];

function createApp(): App {
    global $appPool;
    Router::setMiddlewareAliases([
        'auth' => \App\Middleware\AuthMiddleware::class,
        'throttle' => \App\Middleware\ThrottleMiddleware::class,
        'cors' => \App\Middleware\CorsMiddleware::class,
        'json' => \App\Middleware\JsonMiddleware::class,
    ]);
    $app = new App(BASE_PATH);
    $app->boot();
    $_ENV['THROTTLE_FALLBACK'] = 'disabled';
    putenv('THROTTLE_FALLBACK=disabled');
    $app->loadRoutes(BASE_PATH . '/routes/api.php');
    ensureTablesCreated();
    return $app;
}

function dispatch(App $app, string $method, string $path, array $body = [], array $headers = []): Response {
    $queryParams = [];
    $pathParts = explode('?', $path, 2);
    $cleanPath = $pathParts[0];
    if (isset($pathParts[1])) parse_str($pathParts[1], $queryParams);
    $request = new Request($method, $cleanPath, $queryParams, $headers, $body, '127.0.0.1');
    try {
        return $app->router->dispatch($request);
    } catch (ValidationException $e) {
        return $e->toResponse();
    }
}

function responseBody(Response $response): string {
    ob_start();
    $response->send();
    return ob_get_clean() ?: '';
}

function jsonOf(Response $response): array {
    return json_decode(responseBody($response), true) ?? [];
}

function headersOf(Response $response): string {
    return implode("\n", $response->getHeaders());
}

$tablesCreated = false;
function ensureTablesCreated(): void {
    global $tablesCreated;
    if ($tablesCreated) return;
    $tablesCreated = true;
    $dbPath = BASE_PATH . '/storage/test.db';
    if (file_exists($dbPath)) @unlink($dbPath);
    $files = glob(BASE_PATH . '/database/migrations/*.php') ?: [];
    sort($files);
    foreach ($files as $file) {
        $migration = require $file;
        if (is_object($migration) && method_exists($migration, 'up')) {
            try { $migration->up(); } catch (\Throwable) {}
        }
    }
    try {
        $pdo = Database::connection();
        if (!$pdo->inTransaction()) { $pdo->beginTransaction(); }
    } catch (\Throwable) {}
}

// ==================================================================
// RUN TESTS
// ==================================================================

echo "\n=== SIROPHP COMPREHENSIVE FUNCTIONAL TESTS ===\n\n";

// ==================== 1-2. HEALTH & ROOT ====================
echo "--- [1-2] Health & Root ---\n";

$app = createApp();
test('Health endpoint returns 200 with correct structure', function () use ($app) {
    $r = dispatch($app, 'GET', '/health');
    assertEq(200, $r->statusCode());
    $body = jsonOf($r);
    assertArrayHasKey('success', $body);
    assertTrue($body['success']);
    assertArrayHasKey('data', $body);
    assertEq('healthy', $body['data']['status']);
    assertContains('connected', $body['data']['database']);
    assertArrayHasKey('version', $body['data']);
    assertArrayHasKey('php', $body['data']);
    assertArrayHasKey('time', $body['data']);
});

test('Root endpoint returns 200 with welcome message', function () use ($app) {
    $r = dispatch($app, 'GET', '/');
    assertEq(200, $r->statusCode());
    $body = jsonOf($r);
    assertTrue($body['success']);
    assertArrayHasKey('message', $body);
    assertArrayHasKey('data', $body);
    assertArrayHasKey('name', $body['data']);
    assertArrayHasKey('version', $body['data']);
});

// ==================== 3-11. AUTH FLOW ====================
echo "\n--- [3-11] Auth Flow ---\n";

// IMPORTANT: Auth tests MUST share the same app instance because each
// createApp() starts a new DB transaction, losing data from previous apps.
$regEmail = 'func_' . uniqid() . '@example.com';
$regPassword = 'secret123';

$authApp = createApp();

test('Auth register with valid data returns 201 with JWT token', function () use ($authApp, $regEmail, $regPassword) {
    $r = dispatch($authApp, 'POST', '/api/auth/register', [
        'name' => 'Func Test User',
        'email' => $regEmail,
        'password' => $regPassword,
    ]);
    assertEq(201, $r->statusCode());
    $body = jsonOf($r);
    assertTrue($body['success']);
    assertArrayHasKey('data', $body);
    assertArrayHasKey('token', $body['data']);
    assertArrayHasKey('refresh_token', $body['data']);
    assertArrayHasKey('token_type', $body['data']);
    assertEq('Bearer', $body['data']['token_type']);
    assertArrayHasKey('user', $body['data']);
    assertEq($regEmail, $body['data']['user']['email']);
});

test('Auth register duplicate email returns 422', function () use ($authApp, $regEmail, $regPassword) {
    $r = dispatch($authApp, 'POST', '/api/auth/register', [
        'name' => 'Duplicate',
        'email' => $regEmail,
        'password' => $regPassword,
    ]);
    assertEq(422, $r->statusCode());
});

test('Auth register missing fields returns 422', function () use ($authApp) {
    $r = dispatch($authApp, 'POST', '/api/auth/register', ['name' => 'x']);
    assertEq(422, $r->statusCode());
    $r2 = dispatch($authApp, 'POST', '/api/auth/register', []);
    assertEq(422, $r2->statusCode());
});

test('Auth login with valid credentials returns 200 with token pair', function () use ($authApp, $regEmail, $regPassword) {
    $r = dispatch($authApp, 'POST', '/api/auth/login', [
        'email' => $regEmail,
        'password' => $regPassword,
    ]);
    assertEq(200, $r->statusCode());
    $body = jsonOf($r);
    assertTrue($body['success']);
    assertArrayHasKey('data', $body);
    assertArrayHasKey('token', $body['data']);
    assertArrayHasKey('refresh_token', $body['data']);
    assertArrayHasKey('token_type', $body['data']);
    assertArrayHasKey('user', $body['data']);
});

test('Auth login with wrong password returns 401', function () use ($authApp, $regEmail) {
    $r = dispatch($authApp, 'POST', '/api/auth/login', [
        'email' => $regEmail,
        'password' => 'wrongpassword123',
    ]);
    assertEq(401, $r->statusCode());
    $body = jsonOf($r);
    assertFalse($body['success']);
});

// Get tokens from the shared app instance
$loginResp = dispatch($authApp, 'POST', '/api/auth/login', [
    'email' => $regEmail,
    'password' => $regPassword,
]);
$loginBody = jsonOf($loginResp);
$accessToken = $loginBody['data']['token'] ?? '';
$refreshTokenVal = $loginBody['data']['refresh_token'] ?? '';

test('Auth refresh with valid refresh token returns 200', function () use ($authApp, $refreshTokenVal) {
    $r = dispatch($authApp, 'POST', '/api/auth/refresh', ['refresh_token' => $refreshTokenVal]);
    assertEq(200, $r->statusCode());
    $body = jsonOf($r);
    assertTrue($body['success']);
    assertArrayHasKey('token', $body['data']);
    assertArrayHasKey('refresh_token', $body['data']);
});

test('Auth me with valid token returns 200 with user data', function () use ($authApp, $accessToken) {
    $r = dispatch($authApp, 'GET', '/api/auth/me', [], ['Authorization' => 'Bearer ' . $accessToken]);
    assertEq(200, $r->statusCode());
    $body = jsonOf($r);
    assertTrue($body['success']);
    assertArrayHasKey('data', $body);
    assertArrayHasKey('id', $body['data']);
    assertArrayHasKey('email', $body['data']);
    assertArrayHasKey('name', $body['data']);
    assertArrayNotHasKey('password', $body['data']);
});

test('Auth me without token returns 401', function () use ($authApp) {
    $r = dispatch($authApp, 'GET', '/api/auth/me');
    assertEq(401, $r->statusCode());
    $r2 = dispatch($authApp, 'GET', '/api/auth/me', [], ['Authorization' => 'Bearer invalidtoken']);
    assertEq(401, $r2->statusCode());
});

test('Auth logout returns 200', function () use ($authApp, $accessToken) {
    // Note: This test may fail due to a codebase bug:
    // AuthController uses 'use App\Services\User as UserService;'
    // but the actual class is App\Services\UserService.
    $r = dispatch($authApp, 'POST', '/api/auth/logout', [], ['Authorization' => 'Bearer ' . $accessToken]);
    $body = jsonOf($r);
    if ($r->statusCode() === 500 && str_contains(responseBody($r), 'Class')) {
        throw new RuntimeException('Codebase bug: AuthController references App\\Services\\User (line 8) but class is UserService');
    }
    assertEq(200, $r->statusCode());
    assertTrue($body['success']);
});

// ==================== 12. CATEGORIES CRUD ====================
echo "\n--- [12] Categories CRUD ---\n";

$catApp = createApp();
$catCreate = dispatch($catApp, 'POST', '/api/categories', ['name' => 'Test Category'], ['content-type' => 'application/json']);
$catBody = jsonOf($catCreate);
$catId = $catBody['data']['id'] ?? 0;

test('Categories index returns 200 with data', function () use ($catApp) {
    $r = dispatch($catApp, 'GET', '/api/categories');
    assertEq(200, $r->statusCode());
    assertArrayHasKey('data', jsonOf($r));
});

test('Categories store creates and returns 201', function () use ($catCreate) {
    assertEq(201, $catCreate->statusCode());
    global $catId;
    assertGreaterThan(0, $catId);
});

test('Categories show returns 200 for existing', function () use ($catApp, $catId) {
    $r = dispatch($catApp, 'GET', "/api/categories/{$catId}");
    assertEq(200, $r->statusCode());
    assertEq($catId, jsonOf($r)['data']['id']);
});

test('Categories show returns 404 for non-existent', function () use ($catApp) {
    $r = dispatch($catApp, 'GET', '/api/categories/99999');
    assertEq(404, $r->statusCode());
});

test('Categories update returns 200', function () use ($catApp, $catId) {
    $r = dispatch($catApp, 'PUT', "/api/categories/{$catId}", ['name' => 'Updated Category'], ['content-type' => 'application/json']);
    assertInArray($r->statusCode(), [200, 201]);
});

test('Categories delete returns 204', function () use ($catApp, $catId) {
    $r = dispatch($catApp, 'DELETE', "/api/categories/{$catId}");
    assertEq(204, $r->statusCode());
});

// ==================== 13. TAGS CRUD ====================
echo "\n--- [13] Tags CRUD ---\n";

$tagApp = createApp();
$tagCreate = dispatch($tagApp, 'POST', '/api/tags', ['name' => 'Test Tag'], ['content-type' => 'application/json']);
$tagBody = jsonOf($tagCreate);
$tagId = $tagBody['data']['id'] ?? 0;

test('Tags index returns 200', function () use ($tagApp) {
    $r = dispatch($tagApp, 'GET', '/api/tags');
    assertEq(200, $r->statusCode());
});

test('Tags store creates and returns 201', function () use ($tagCreate) {
    assertEq(201, $tagCreate->statusCode());
    global $tagId;
    assertGreaterThan(0, $tagId);
});

test('Tags show returns 200 for existing', function () use ($tagApp, $tagId) {
    $r = dispatch($tagApp, 'GET', "/api/tags/{$tagId}");
    assertEq(200, $r->statusCode());
    assertEq($tagId, jsonOf($r)['data']['id']);
});

test('Tags update returns 200', function () use ($tagApp, $tagId) {
    $r = dispatch($tagApp, 'PUT', "/api/tags/{$tagId}", ['name' => 'Updated Tag'], ['content-type' => 'application/json']);
    assertInArray($r->statusCode(), [200, 201]);
});

test('Tags delete returns 204', function () use ($tagApp, $tagId) {
    $r = dispatch($tagApp, 'DELETE', "/api/tags/{$tagId}");
    assertEq(204, $r->statusCode());
});

// ==================== 14. PRODUCTS CRUD ====================
echo "\n--- [14] Products CRUD ---\n";

$prodApp = createApp();
$prodCreate = dispatch($prodApp, 'POST', '/api/products', [
    'name' => 'Test Product',
    'price' => 29.99,
    'stock' => 100,
    'category' => 'electronics',
    'status' => 'active',
], ['content-type' => 'application/json']);
$prodBody = jsonOf($prodCreate);
$prodId = $prodBody['data']['id'] ?? 0;

test('Products index returns 200 with paginated data', function () use ($prodApp) {
    $r = dispatch($prodApp, 'GET', '/api/products');
    assertEq(200, $r->statusCode());
    assertArrayHasKey('data', jsonOf($r));
});

test('Products store creates and returns 201', function () use ($prodCreate) {
    assertEq(201, $prodCreate->statusCode());
    global $prodId;
    assertGreaterThan(0, $prodId);
});

test('Products show returns 200 for existing', function () use ($prodApp, $prodId) {
    $r = dispatch($prodApp, 'GET', "/api/products/{$prodId}");
    assertEq(200, $r->statusCode());
    assertEq($prodId, jsonOf($r)['data']['id']);
});

test('Products show returns 404 for non-existent', function () use ($prodApp) {
    $r = dispatch($prodApp, 'GET', '/api/products/99999');
    assertEq(404, $r->statusCode());
});

test('Products update returns 200', function () use ($prodApp, $prodId) {
    $r = dispatch($prodApp, 'PUT', "/api/products/{$prodId}", ['name' => 'Updated Product', 'price' => 39.99], ['content-type' => 'application/json']);
    assertInArray($r->statusCode(), [200, 201]);
    if ($r->statusCode() === 200) {
        assertEq('Updated Product', jsonOf($r)['data']['name']);
    }
});

test('Products delete returns 204', function () use ($prodApp, $prodId) {
    $r = dispatch($prodApp, 'DELETE', "/api/products/{$prodId}");
    assertEq(204, $r->statusCode());
});

// ==================== 15. PRODUCTS FILTER ====================
echo "\n--- [15] Products Filtering ---\n";

$filtApp = createApp();
dispatch($filtApp, 'POST', '/api/products', [
    'name' => 'FilterMePlease',
    'price' => 15.00,
    'category' => 'filtercat',
    'status' => 'active',
], ['content-type' => 'application/json']);

test('Products filter by search term', function () use ($filtApp) {
    $r = dispatch($filtApp, 'GET', '/api/products?search=FilterMePlease');
    assertEq(200, $r->statusCode());
});

test('Products filter by category', function () use ($filtApp) {
    $r = dispatch($filtApp, 'GET', '/api/products?category=filtercat');
    assertEq(200, $r->statusCode());
});

test('Products filter by price range', function () use ($filtApp) {
    $r = dispatch($filtApp, 'GET', '/api/products?price_min=5&price_max=50');
    assertEq(200, $r->statusCode());
});

test('Products filter by status', function () use ($filtApp) {
    $r = dispatch($filtApp, 'GET', '/api/products?status=active');
    assertEq(200, $r->statusCode());
});

test('Products filter by sort and order', function () use ($filtApp) {
    $r = dispatch($filtApp, 'GET', '/api/products?sort=price&order=asc');
    assertEq(200, $r->statusCode());
});

// ==================== 16. ORDERS CRUD ====================
echo "\n--- [16] Orders CRUD ---\n";

$ordApp = createApp();
$ordCreate = dispatch($ordApp, 'POST', '/api/orders', [
    'customer_name' => 'John Doe',
    'customer_email' => 'john@example.com',
    'total' => 199.99,
    'status' => 'pending',
], ['content-type' => 'application/json']);
$ordBody = jsonOf($ordCreate);
$ordId = $ordBody['data']['id'] ?? 0;

test('Orders index returns 200', function () use ($ordApp) {
    $r = dispatch($ordApp, 'GET', '/api/orders');
    assertEq(200, $r->statusCode());
});

test('Orders store creates and returns 201', function () use ($ordCreate) {
    assertEq(201, $ordCreate->statusCode());
    global $ordId;
    assertGreaterThan(0, $ordId);
});

test('Orders show returns 200 for existing', function () use ($ordApp, $ordId) {
    $r = dispatch($ordApp, 'GET', "/api/orders/{$ordId}");
    assertEq(200, $r->statusCode());
    assertEq($ordId, jsonOf($r)['data']['id']);
});

test('Orders update returns 200', function () use ($ordApp, $ordId) {
    $r = dispatch($ordApp, 'PUT', "/api/orders/{$ordId}", ['status' => 'completed'], ['content-type' => 'application/json']);
    assertInArray($r->statusCode(), [200, 201]);
});

test('Orders delete returns 204', function () use ($ordApp, $ordId) {
    $r = dispatch($ordApp, 'DELETE', "/api/orders/{$ordId}");
    assertEq(204, $r->statusCode());
});

// ==================== 17. POSTS CRUD ====================
echo "\n--- [17] Posts CRUD ---\n";

$postApp = createApp();
$postCreate = dispatch($postApp, 'POST', '/api/posts', [
    'title' => 'Test Post Title',
    'body' => 'This body content has enough characters to pass validation.',
    'locale' => 'en',
    'status' => 'published',
], ['content-type' => 'application/json']);
$postBody = jsonOf($postCreate);
$postId = $postBody['data']['id'] ?? 0;

test('Posts index returns 200', function () use ($postApp) {
    $r = dispatch($postApp, 'GET', '/api/posts');
    assertEq(200, $r->statusCode());
});

test('Posts store creates and returns 201', function () use ($postCreate) {
    assertEq(201, $postCreate->statusCode());
    global $postId;
    assertGreaterThan(0, $postId);
});

test('Posts show returns 200 for existing', function () use ($postApp, $postId) {
    $r = dispatch($postApp, 'GET', "/api/posts/{$postId}");
    assertEq(200, $r->statusCode());
    assertEq($postId, jsonOf($r)['data']['id']);
});

test('Posts update returns 200', function () use ($postApp, $postId) {
    $r = dispatch($postApp, 'PUT', "/api/posts/{$postId}", ['title' => 'Updated Post Title'], ['content-type' => 'application/json']);
    assertInArray($r->statusCode(), [200, 201]);
});

test('Posts delete returns 200 or 204', function () use ($postApp, $postId) {
    $r = dispatch($postApp, 'DELETE', "/api/posts/{$postId}");
    assertInArray($r->statusCode(), [200, 204]);
});

// ==================== 18. USERS CRUD ====================
echo "\n--- [18] Users CRUD ---\n";

$userApp = createApp();
// Register a user to get auth token
$userReg = dispatch($userApp, 'POST', '/api/auth/register', [
    'name' => 'Admin User',
    'email' => 'admin_' . uniqid() . '@example.com',
    'password' => 'adminpass123',
]);
$userRegBody = jsonOf($userReg);
$adminToken = $userRegBody['data']['token'] ?? '';

test('Users index returns 200 (public)', function () use ($userApp) {
    $r = dispatch($userApp, 'GET', '/api/users');
    assertEq(200, $r->statusCode());
});

test('Users store without auth returns 401', function () use ($userApp) {
    $r = dispatch($userApp, 'POST', '/api/users', [
        'name' => 'New User',
        'email' => 'new_' . uniqid() . '@example.com',
        'password' => 'password123',
    ]);
    assertEq(401, $r->statusCode());
});

test('Users store with auth returns 201', function () use ($userApp, $adminToken) {
    $r = dispatch($userApp, 'POST', '/api/users', [
        'name' => 'Created User',
        'email' => 'created_' . uniqid() . '@example.com',
        'password' => 'password123',
    ], ['Authorization' => 'Bearer ' . $adminToken, 'content-type' => 'application/json']);
    assertEq(201, $r->statusCode());
    assertArrayHasKey('id', jsonOf($r)['data']);
});

test('Users show returns 200 for existing', function () use ($userApp) {
    $list = dispatch($userApp, 'GET', '/api/users');
    $users = jsonOf($list)['data'] ?? [];
    $userId = $users[0]['id'] ?? 1;
    $r = dispatch($userApp, 'GET', "/api/users/{$userId}");
    assertEq(200, $r->statusCode());
    assertArrayHasKey('id', jsonOf($r)['data']);
});

test('Users update with auth returns 200', function () use ($userApp, $adminToken) {
    $list = dispatch($userApp, 'GET', '/api/users');
    $users = jsonOf($list)['data'] ?? [];
    $userId = $users[0]['id'] ?? 1;
    $r = dispatch($userApp, 'PUT', "/api/users/{$userId}", ['name' => 'Updated Name'], ['Authorization' => 'Bearer ' . $adminToken, 'content-type' => 'application/json']);
    assertInArray($r->statusCode(), [200, 201]);
});

test('Users delete with auth returns 204', function () use ($userApp, $adminToken) {
    $list = dispatch($userApp, 'GET', '/api/users');
    $users = jsonOf($list)['data'] ?? [];
    $targetId = null;
    foreach ($users as $u) {
        if (($u['role'] ?? 'user') !== 'admin') {
            $targetId = $u['id'];
            break;
        }
    }
    if ($targetId === null) {
        // Create a deletable user first
        $cr = dispatch($userApp, 'POST', '/api/users', [
            'name' => 'Delete Me', 'email' => 'del_' . uniqid() . '@example.com', 'password' => 'pass1234',
        ], ['Authorization' => 'Bearer ' . $adminToken, 'content-type' => 'application/json']);
        $targetId = jsonOf($cr)['data']['id'] ?? null;
    }
    if ($targetId !== null) {
        $r = dispatch($userApp, 'DELETE', "/api/users/{$targetId}", [], ['Authorization' => 'Bearer ' . $adminToken]);
        assertEq(204, $r->statusCode());
    } else {
        throw new RuntimeException('Could not find or create a deletable user');
    }
});

// ==================== 19. PROFILE ====================
echo "\n--- [19] Profile ---\n";

$profApp = createApp();
test('Profile with locale=en returns 200 with greeting', function () use ($profApp) {
    $r = dispatch($profApp, 'GET', '/api/profile?locale=en&name=Alice');
    assertEq(200, $r->statusCode());
    $body = jsonOf($r);
    assertTrue($body['success']);
    assertEq('en', $body['data']['locale']);
    assertContains('Alice', $body['message']);
});

test('Profile with locale=vi returns 200', function () use ($profApp) {
    $r = dispatch($profApp, 'GET', '/api/profile?locale=vi&name=Alice');
    assertEq(200, $r->statusCode());
    assertEq('vi', jsonOf($r)['data']['locale']);
});

test('Profile without parameters uses defaults', function () use ($profApp) {
    $r = dispatch($profApp, 'GET', '/api/profile');
    assertEq(200, $r->statusCode());
    $body = jsonOf($r);
    assertEq('en', $body['data']['locale']);
    assertEq('Guest', $body['data']['name']);
});

// ==================== 20. 404 ====================
echo "\n--- [20] 404 Non-existent Routes ---\n";

$nfApp = createApp();
test('Non-existent API route returns 404', function () use ($nfApp) {
    $r = dispatch($nfApp, 'GET', '/api/nonexistent');
    assertEq(404, $r->statusCode());
});

test('Non-existent root route returns 404', function () use ($nfApp) {
    $r = dispatch($nfApp, 'GET', '/nonexistent');
    assertEq(404, $r->statusCode());
});

// ==================== 21. CORS ====================
echo "\n--- [21] CORS ---\n";

$corsApp = createApp();

test('OPTIONS request returns 204', function () use ($corsApp) {
    // The Siro Core framework handles OPTIONS preflight natively
    $r = dispatch($corsApp, 'OPTIONS', '/api/products', [], ['origin' => 'http://localhost:8080']);
    assertEq(204, $r->statusCode());
});

test('GET to /api endpoints includes CORS headers from middleware', function () use ($corsApp) {
    $r = dispatch($corsApp, 'GET', '/api/products', [], ['origin' => 'http://localhost:8080']);
    $h = headersOf($r);
    assertContains('Access-Control-Allow-Origin', $h);
    assertContains('Access-Control-Allow-Methods', $h);
    assertContains('Access-Control-Allow-Headers', $h);
    assertContains('Vary: Origin', $h);
});

// ==================== 22. SECURITY HEADERS ====================
echo "\n--- [22] Security Headers ---\n";

$secApp = createApp();
$secResp = dispatch($secApp, 'GET', '/api/products');
$secHeaders = headersOf($secResp);

test('X-Frame-Options header is DENY', function () use ($secHeaders) {
    assertContains('X-Frame-Options: DENY', $secHeaders);
});

test('X-Content-Type-Options header is nosniff', function () use ($secHeaders) {
    assertContains('X-Content-Type-Options: nosniff', $secHeaders);
});

test('X-XSS-Protection header is present', function () use ($secHeaders) {
    assertContains('X-XSS-Protection', $secHeaders);
});

test('Referrer-Policy header is present', function () use ($secHeaders) {
    assertContains('Referrer-Policy', $secHeaders);
});

test('Permissions-Policy header is present', function () use ($secHeaders) {
    assertContains('Permissions-Policy', $secHeaders);
});

test('Content-Security-Policy header is present', function () use ($secHeaders) {
    assertContains('Content-Security-Policy', $secHeaders);
});

test('All required security headers present', function () use ($secHeaders) {
    foreach (['X-Frame-Options', 'X-Content-Type-Options', 'X-XSS-Protection', 'Referrer-Policy', 'Permissions-Policy', 'Content-Security-Policy'] as $header) {
        assertContains($header, $secHeaders, "Missing: $header");
    }
});

// ==================================================================
// SUMMARY
// ==================================================================
echo "\n=====================================\n";
echo "       TEST SUMMARY\n";
echo "=====================================\n";
foreach ($results as $r) {
    echo $r . "\n";
}
echo "=====================================\n";
$total = $passed + $failed;
echo "Total: $total  |  PASS: $passed  |  FAIL: $failed\n";

if ($failed > 0) {
    echo "\n⚠️  SOME TESTS FAILED!\n";
    exit(1);
}
echo "\n✓ ALL TESTS PASSED!\n";
exit(0);
