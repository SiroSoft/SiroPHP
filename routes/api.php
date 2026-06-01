<?php

declare(strict_types=1);

use App\Controllers\AuthController;
use Siro\Core\Middleware\CorsMiddleware;
use Siro\Core\Middleware\JsonMiddleware;
use App\Middleware\SecurityHeadersMiddleware;
use Siro\Core\Lang;
use Siro\Core\Metrics;
use Siro\Core\Request;
use Siro\Core\Response;

/** @var \Siro\Core\App $app */

Metrics::init('siro', \Siro\Core\Env::get('APP_DEBUG', 'false') === 'true');
Metrics::registerRoute($app->router);

// ─── API Route Reference ─────────────────────────────────────────
//
// Base URL: http://localhost:8080/api/
//
// Standard CRUD pattern:
//   GET    /api/{resource}          List all (paginated)
//   POST   /api/{resource}          Create new
//   GET    /api/{resource}/{id}     Get one
//   PUT    /api/{resource}/{id}     Update one
//   DELETE /api/{resource}/{id}     Delete one
//
// Auth (public):
//   POST   /api/auth/register          Register new account
//   POST   /api/auth/login             Login (returns JWT)
//   POST   /api/auth/refresh           Refresh expired JWT
//   POST   /api/auth/forgot-password   Request password reset
//   POST   /api/auth/reset-password    Complete password reset
//   POST   /api/auth/verify-email      Verify email address
//
// Auth (protected — requires JWT):
//   GET    /api/auth/me                Current user profile
//   POST   /api/auth/logout            Logout (revoke tokens)
//
// CRUD Resources (protected):
//   /api/users           User management
//   /api/products        Product catalog
//   /api/orders          Order management
//   /api/categories      Category management
//   /api/tags            Tag management
//   /api/posts           Blog posts
//
// Profile & Settings (protected):
//   GET    /api/profile                User profile (with i18n greeting)
//   PUT    /api/profile                Update profile
//   PUT    /api/profile/password       Change password
//   GET    /api/settings               App settings
//   PUT    /api/settings               Update app settings
//
// Other (protected):
//   PATCH  /api/orders/{id}/status     Update order status
//   GET    /api/dashboard/stats        Dashboard statistics
//   POST   /api/upload                 File upload
//   POST   /api/upload/avatar          Avatar upload
//
// System (no auth):
//   GET    /                           Welcome page
//   GET    /health                     Health check (combined)
//   GET    /health/live                Liveness probe
//   GET    /health/ready               Readiness probe (checks DB)
//
// ---------------------------------------------------------------------------
// System / Utility Routes (no auth)
// ---------------------------------------------------------------------------
$app->router->get('/favicon.ico', fn () => Response::noContent());
$app->router->get('/robots.txt', fn () => Response::raw("User-agent: *\nDisallow: /", 'text/plain'));
$app->router->get('/.well-known/security.txt', fn () => Response::raw("Contact: https://github.com/SiroSoft/SiroPHP/issues\nPolicy: https://github.com/SiroSoft/siro-core/blob/main/docs/SECURITY.md", 'text/plain'));

// Liveness probe (no DB dependency)
$app->router->get('/health/live', function (): array {
    return [
        'success' => true,
        'message' => 'OK',
        'data' => [
            'status' => 'alive',
            'time' => date('c'),
        ],
    ];
})->middleware('throttle:30,1');

// Readiness probe (checks DB)
$app->router->get('/health/ready', function (): array {
    $dbOk = false;
    try {
        \Siro\Core\Database::connection()->query('SELECT 1');
        $dbOk = true;
    } catch (\Throwable) {
    }
    return [
        'success' => true,
        'message' => 'OK',
        'data' => [
            'status' => $dbOk ? 'ready' : 'degraded',
            'version' => \Siro\Core\Console::getVersion(),
            'database' => $dbOk ? 'connected' : 'unreachable',
            'time' => date('c'),
        ],
    ];
});

// Combined health endpoint (throttled)
$app->router->get('/health', function (): array {
    $dbOk = false;
    try {
        \Siro\Core\Database::connection()->query('SELECT 1');
        $dbOk = true;
    } catch (\Throwable) {
    }
    $data = [
        'status' => 'healthy',
        'database' => $dbOk ? 'connected' : 'unreachable',
        'time' => date('c'),
    ];
    if (\Siro\Core\Env::bool('APP_DEBUG', false)) {
        $data['app_env'] = \Siro\Core\Env::get('APP_ENV', 'local');
    }
    return [
        'success' => true,
        'message' => 'OK',
        'data' => $data,
    ];
})->middleware('throttle:30,1');

// Root welcome
$app->router->get('/', function (Request $req): mixed {
    $accept = strval($req->header('accept', ''));
    $isBrowser = str_contains($accept, 'text/html') && !str_contains($accept, 'application/json');
    if ($isBrowser) {
        $isDebug = \Siro\Core\Env::bool('APP_DEBUG', false);
        $env = \Siro\Core\Env::get('APP_ENV', 'local');
        $showDevDashboard = $isDebug && $env === 'local';

        $fileName = $showDevDashboard ? 'index.html' : 'index-prod.html';
        $file = __DIR__ . '/../public/' . $fileName;
        if (file_exists($file)) {
            $html = file_get_contents($file);
            return Response::raw($html !== false ? $html : '', 'text/html; charset=utf-8');
        }
    }
    return [
        'success' => true,
        'message' => Lang::get('messages.welcome'),
        'data' => [
            'name' => 'Siro API Framework',
            'version' => \Siro\Core\Console::getVersion(),
            'locale' => Lang::locale(),
        ],
        'meta' => [],
    ];
});

// ---------------------------------------------------------------------------
// API v1 — Authenticated routes
// ---------------------------------------------------------------------------
$app->router->group('/api', [SecurityHeadersMiddleware::class, CorsMiddleware::class, 'version', 'etag', 'metrics', 'audit'], function (\Siro\Core\Router $router): void {

    // -- Auth (public) --
    $router->post('/auth/register', [AuthController::class, 'register'])
        ->middleware([JsonMiddleware::class, 'throttle:30,1']);

    $router->post('/auth/login', [AuthController::class, 'login'])
        ->middleware([JsonMiddleware::class, 'throttle:60,1']);

    $router->post('/auth/refresh', [AuthController::class, 'refresh'])
        ->middleware([JsonMiddleware::class, 'throttle:30,1']);

    $router->post('/auth/forgot-password', [AuthController::class, 'forgotPassword'])
        ->middleware([JsonMiddleware::class, 'throttle:10,1']);

    $router->post('/auth/reset-password', [AuthController::class, 'resetPassword'])
        ->middleware([JsonMiddleware::class, 'throttle:10,1']);

    $router->post('/auth/verify-email', [AuthController::class, 'verifyEmail'])
        ->middleware([JsonMiddleware::class, 'throttle:10,1']);

    // -- Auth (protected) --
    $router->get('/auth/me', [AuthController::class, 'me'])
        ->middleware(['auth', 'throttle:120,1']);

    $router->post('/auth/logout', [AuthController::class, 'logout'])
        ->middleware(['auth', 'throttle:60,1']);

    // -- CRUD Resources --
    $router->resource('products', \App\Controllers\ProductController::class, ['auth', 'throttle:60,1']);
    $router->resource('categories', \App\Controllers\CategoryController::class, ['auth', 'throttle:60,1']);
    $router->resource('tags', \App\Controllers\TagController::class, ['auth', 'throttle:60,1']);
    $router->resource('orders', \App\Controllers\OrderController::class, ['auth', 'throttle:60,1']);
    $router->resource('posts', \App\Controllers\PostController::class, ['auth', 'throttle:60,1']);
    $router->resource('users', \App\Controllers\UserController::class, ['auth', 'throttle:60,1']);

    // -- File Upload (using App\Support\Uploader) --
    $router->post('/upload/avatar', fn(Request $req): Response => \App\Support\Uploader::response($req, 'avatar', 'avatars'))
        ->middleware(['auth', 'throttle:10,1']);

    $router->post('/upload', fn(Request $req): Response => \App\Support\Uploader::response($req, 'file', 'uploads'))
        ->middleware(['auth', 'throttle:10,1']);

    // -- Profile --
    $router->get('/profile', function (Request $req): array {
        $locale = $req->queryString('locale', 'en');
        if (!in_array($locale, ['en', 'vi'])) $locale = 'en';
        Lang::setLocale($locale);

        $name = $req->query('name', 'Guest');
        $greeting = Lang::get('messages.greeting', ['name' => $name]);
        $messagesCount = Lang::has('validation') ? count((array) Lang::get('validation')) : 0;

        return [
            'success' => true,
            'message' => $greeting,
            'data' => [
                'name' => $name,
                'locale' => $locale,
                'greeting' => $greeting,
                'messages_count' => $messagesCount,
                'available_locales' => ['en', 'vi'],
            ],
        ];
    })->middleware(['auth']);

    $router->put('/profile', [\App\Controllers\UserController::class, 'updateProfile'])
        ->middleware(['auth', JsonMiddleware::class]);

    $router->put('/profile/password', function (Request $req): Response {
        $data = $req->all();
        $currentPassword = is_string($data['current_password'] ?? null) ? $data['current_password'] : '';
        $newPassword = is_string($data['new_password'] ?? null) ? $data['new_password'] : '';
        $user = $req->user();
        $userId = is_numeric($user['id'] ?? null) ? (int) $user['id'] : 0;
        if ($userId <= 0) {
            return Response::error('Unauthorized', 401);
        }
        if ($currentPassword === '' || $newPassword === '') {
            return Response::error('Validation failed', 422, [
                'current_password' => ['Current password is required'],
                'new_password' => ['New password is required'],
            ]);
        }
        $existingUser = \App\Models\User::find($userId);
        if ($existingUser === null) {
            return Response::error('User not found', 404);
        }
        $existingPassword = $existingUser->getAttribute('password');
        if (!is_string($existingPassword) || !password_verify($currentPassword, $existingPassword)) {
            return Response::error('Current password is incorrect', 400);
        }
        $existingUser->update(['password' => password_hash($newPassword, PASSWORD_BCRYPT)]);
        return Response::success(null, 'Password changed');
    })->middleware(['auth', JsonMiddleware::class]);

    // -- Settings --
    $router->get('/settings', function (Request $req): Response {
        $userData = $req->user();
        $role = is_array($userData) && isset($userData['role']) ? $userData['role'] : '';
        if ($role !== \App\Enums\Role::ADMIN) {
            return Response::error('Forbidden', 403);
        }
        try {
            $rows = \Siro\Core\Database::select("SELECT `key`, `value` FROM settings");
            $settings = [];
            foreach ($rows as $row) {
                $key = isset($row['key']) && is_string($row['key']) ? $row['key'] : '';
                $value = isset($row['value']) ? $row['value'] : '';
                if ($key !== '') {
                    $settings[$key] = $value;
                }
            }
            return Response::success($settings ?: [
                'app_name' => 'SiroPHP',
                'locale' => 'en',
                'timezone' => 'UTC',
            ], 'Settings retrieved');
        } catch (\Throwable) {
            return Response::success([
                'app_name' => 'SiroPHP',
                'locale' => 'en',
                'timezone' => 'UTC',
            ], 'Settings retrieved (defaults)');
        }
    })->middleware(['auth', JsonMiddleware::class]);

    $router->put('/settings', function (Request $req): Response {
        $userData = $req->user();
        $role = is_array($userData) && isset($userData['role']) ? $userData['role'] : '';
        if ($role !== \App\Enums\Role::ADMIN) {
            return Response::error('Forbidden', 403);
        }
        $data = $req->all();
        if ($data === []) {
            return Response::error('No settings provided', 422);
        }
        try {
            foreach ($data as $key => $value) {
                $strKey = (string) $key;
                $strValue = is_scalar($value) ? (string) $value : '';
                if ($strKey === '') continue;
                $existing = \Siro\Core\Database::first("SELECT id FROM settings WHERE `key` = ?", [$strKey]);
                if ($existing !== null) {
                    \Siro\Core\Database::execute("UPDATE settings SET `value` = ? WHERE `key` = ?", [$strValue, $strKey]);
                } else {
                    \Siro\Core\Database::execute("INSERT INTO settings (`key`, `value`) VALUES (?, ?)", [$strKey, $strValue]);
                }
            }
            return Response::success($data, 'Settings updated');
        } catch (\Throwable $e) {
            return Response::error('Settings update failed: ' . $e->getMessage(), 500);
        }
    })->middleware(['auth', JsonMiddleware::class]);

    // -- Orders: status update --
    $router->patch('/orders/{id}/status', [\App\Controllers\OrderController::class, 'updateStatus'])
        ->middleware(['auth', JsonMiddleware::class, 'throttle:60,1']);

    // -- Server Info (rate-limited, no sensitive DB details) --
    $router->get('/server/info', function (): Response {
        $sapi = php_sapi_name();
        $serverName = match (true) {
            str_contains($sapi, 'frankenphp') => 'FrankenPHP ' . PHP_VERSION,
            $sapi === 'cli-server' => 'PHP Dev Server ' . PHP_VERSION,
            default => 'PHP ' . PHP_VERSION . ' (' . $sapi . ')',
        };

        // Detect database driver (name only, no version/PDO reflection)
        $dbDriver = 'unknown';
        try {
            $conn = \Siro\Core\Database::connection();
            $dbDriver = $conn->getAttribute(\PDO::ATTR_DRIVER_NAME);
        } catch (\Throwable) {}

        return Response::success([
            'server' => $serverName,
            'php' => PHP_VERSION,
            'db' => $dbDriver,
            'time' => date('c'),
        ]);
    })->middleware(['throttle:10,1']);

    // -- Dashboard --
    $router->get('/dashboard/stats', function (): Response {
        try {
            $countRow = \Siro\Core\Database::first("SELECT COUNT(*) as count FROM users");
            $userCount = is_array($countRow) && isset($countRow['count']) && is_numeric($countRow['count']) ? (int) $countRow['count'] : 0;
            $orderRow = \Siro\Core\Database::first("SELECT COUNT(*) as count FROM orders");
            $orderCount = is_array($orderRow) && isset($orderRow['count']) && is_numeric($orderRow['count']) ? (int) $orderRow['count'] : 0;
            $productRow = \Siro\Core\Database::first("SELECT COUNT(*) as count FROM products");
            $productCount = is_array($productRow) && isset($productRow['count']) && is_numeric($productRow['count']) ? (int) $productRow['count'] : 0;
            $recentUsers = \Siro\Core\Database::select("SELECT id, name, email, created_at FROM users ORDER BY id DESC LIMIT 5");
        } catch (\Throwable) {
            $userCount = 0;
            $orderCount = 0;
            $productCount = 0;
            $recentUsers = [];
        }
        return Response::success([
            'total_users' => $userCount,
            'active_users' => $userCount,
            'total_orders' => $orderCount,
            'total_products' => $productCount,
            'total_revenue' => 0.0,
            'recent_activity' => array_map(fn($u) => [
                'id' => isset($u['id']) ? $u['id'] : null,
                'action' => 'User registered',
                'description' => (isset($u['name']) && is_string($u['name']) ? $u['name'] : '') . ' joined',
                'user' => isset($u['name']) ? $u['name'] : null,
                'created_at' => isset($u['created_at']) ? $u['created_at'] : null,
            ], $recentUsers),
            'api_status' => [
                'status' => 'healthy',
                'version' => \Siro\Core\Console::getVersion(),
                'uptime' => 3600 * 24 * 30,
                'response_time' => 0.2,
            ],
            'orders_by_status' => ['pending' => 0, 'processing' => 0, 'completed' => 0],
            'monthly_revenue' => [
                ['month' => 'Jan', 'revenue' => 0],
                ['month' => 'Feb', 'revenue' => 0],
                ['month' => 'Mar', 'revenue' => 0],
            ],
        ], 'Dashboard stats');
    })->middleware(['auth']);
});
