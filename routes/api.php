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

// Prevent 404 noise from browser requests
$app->router->get('/favicon.ico', fn () => Response::noContent());
$app->router->get('/robots.txt', fn () => Response::raw("User-agent: *\nDisallow: /", 'text/plain'));
$app->router->get('/.well-known/security.txt', fn () => Response::raw("Contact: https://github.com/SiroSoft/SiroPHP/issues\nPolicy: https://github.com/SiroSoft/siro-core/blob/main/docs/SECURITY.md", 'text/plain'));

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

// API Versioning registration (currently disabled — routes mounted at /api without version prefix)
// \Siro\Core\Middleware\VersionMiddleware::register(1, '/api/v1');
// \Siro\Core\Middleware\VersionMiddleware::register(2, '/api/v2');

$app->router->get('/', function (Request $req): mixed {
    $accept = strval($req->header('accept', ''));

    $isBrowser = str_contains($accept, 'text/html') && !str_contains($accept, 'application/json');

    if ($isBrowser) {
        $file = __DIR__ . '/../public/index.html';
        if (file_exists($file)) {
            $html = file_get_contents($file);
            return Response::raw($html !== false ? $html : '', 'text/html; charset=utf-8');
        }
    }

    // Default: Return JSON API response
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

$app->router->group('/api', [SecurityHeadersMiddleware::class, CorsMiddleware::class, 'version', 'etag', 'metrics', 'audit'], function (\Siro\Core\Router $router): void {
    // Public auth routes
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

    // Protected auth routes
    $router->get('/auth/me', [AuthController::class, 'me'])
        ->middleware(['auth', 'throttle:120,1']);

    $router->post('/auth/logout', [AuthController::class, 'logout'])
        ->middleware(['auth', 'throttle:60,1']);

    $router->resource('products', \App\Controllers\ProductController::class, ['auth', 'throttle:60,1']);
    $router->resource('categories', \App\Controllers\CategoryController::class, ['auth', 'throttle:60,1']);
    $router->resource('tags', \App\Controllers\TagController::class, ['auth', 'throttle:60,1']);
    $router->resource('orders', \App\Controllers\OrderController::class, ['auth', 'throttle:60,1']);
    $router->resource('posts', \App\Controllers\PostController::class, ['auth', 'throttle:60,1']);
    $router->resource('users', \App\Controllers\UserController::class, ['auth', 'throttle:60,1']);

    // Upload
    $router->post('/upload/avatar', function (Request $req): Response {
        try {
            $file = $req->file('avatar');
            if ($file === null || !$file->isValid()) {
                return Response::error('No file uploaded', 422);
            }
            $path = $file->store('avatars');
            $baseUrl = rtrim((string) \Siro\Core\Env::get('APP_URL', 'http://localhost:8080'), '/');
            return Response::success([
                'path' => $path,
                'url' => $baseUrl . '/storage/' . $path,
                'original_name' => $file->getClientOriginalName(),
                'size' => $file->getSize(),
                'mime' => $file->getMimeType(),
            ], 'Avatar uploaded');
        } catch (\Throwable $e) {
            return Response::error($e->getMessage(), 500);
        }
    })->middleware(['auth', 'throttle:10,1']);

    $router->post('/upload', function (Request $req): Response {
        try {
            $file = $req->file('file');
            if ($file === null || !$file->isValid()) {
                return Response::error('No file uploaded', 422);
            }
            $path = $file->store('uploads');
            $baseUrl = rtrim((string) \Siro\Core\Env::get('APP_URL', 'http://localhost:8080'), '/');
            return Response::success([
                'path' => $path,
                'url' => $baseUrl . '/storage/' . $path,
                'original_name' => $file->getClientOriginalName(),
                'size' => $file->getSize(),
                'mime' => $file->getMimeType(),
            ], 'File uploaded', 201);
        } catch (\Throwable $e) {
            return Response::error($e->getMessage(), 500);
        }
    })->middleware(['auth', 'throttle:10,1']);

    // L8: GET /profile performs locale state changes. Consider POST for mutations.
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
        $currentPassword = $data['current_password'] ?? '';
        $newPassword = $data['new_password'] ?? '';
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

    $router->get('/settings', function (): Response {
        try {
            $rows = \Siro\Core\Database::select("SELECT `key`, `value` FROM settings");
            $settings = [];
            foreach ($rows as $row) {
                $settings[$row['key']] = $row['value'];
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
        $data = $req->all();
        if (!is_array($data) || $data === []) {
            return Response::error('No settings provided', 422);
        }
        try {
            foreach ($data as $key => $value) {
                $existing = \Siro\Core\Database::first("SELECT id FROM settings WHERE `key` = ?", [(string) $key]);
                if ($existing !== null) {
                    \Siro\Core\Database::execute("UPDATE settings SET `value` = ? WHERE `key` = ?", [(string) $value, (string) $key]);
                } else {
                    \Siro\Core\Database::execute("INSERT INTO settings (`key`, `value`) VALUES (?, ?)", [(string) $key, (string) $value]);
                }
            }
            return Response::success($data, 'Settings updated');
        } catch (\Throwable $e) {
            return Response::error('Settings update failed: ' . $e->getMessage(), 500);
        }
    })->middleware(['auth', JsonMiddleware::class]);

    $router->patch('/orders/{id}/status', [\App\Controllers\OrderController::class, 'updateStatus'])
        ->middleware(['auth', JsonMiddleware::class, 'throttle:60,1']);

    $router->get('/dashboard/stats', function (): Response {
        try {
            $userCount = (int) (\Siro\Core\Database::first("SELECT COUNT(*) as count FROM users")['count'] ?? 0);
            $orderCount = (int) (\Siro\Core\Database::first("SELECT COUNT(*) as count FROM orders")['count'] ?? 0);
            $productCount = (int) (\Siro\Core\Database::first("SELECT COUNT(*) as count FROM products")['count'] ?? 0);
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
                'id' => $u['id'],
                'action' => 'User registered',
                'description' => $u['name'] . ' joined',
                'user' => $u['name'],
                'created_at' => $u['created_at'],
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
