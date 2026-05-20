<?php

declare(strict_types=1);

/**
 * Debug CLI Demo — FE Bug → BE Debug → Fix → Verify
 *
 * Usage: php scripts/debug-demo.php
 *
 * Simulates real-world bugs reported by FE and debugs them
 * using the full CLI debug workflow.
 */

// ── Bootstrap ──────────────────────────────────────────

require_once __DIR__ . '/../vendor/autoload.php';

use Siro\Core\App;
use Siro\Core\Database;
use Siro\Core\Debug\TraceData;
use Siro\Core\Logger;
use Siro\Core\Request;
use Siro\Core\Response;
use Siro\Core\Route;
use Siro\Core\Router;

$basePath = dirname(__DIR__);

// Load .env
$envFile = $basePath . '/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (str_starts_with(trim($line), '#')) continue;
        if (str_contains($line, '=')) {
            [$key, $value] = explode('=', $line, 2);
            putenv(trim($key) . '=' . trim($value));
            $_ENV[trim($key)] = trim($value);
        }
    }
}

putenv('APP_DEBUG=true');
putenv('APP_ENV=local');

// Boot framework
Logger::boot($basePath);
Database::configure([
    'driver' => 'sqlite',
    'database' => ':memory:',
    'capture_queries' => true,
], 'default');

Router::setMiddlewareAliases([
    'json' => \Siro\Core\Middleware\JsonMiddleware::class,
]);

// Set up Route facade — auto-creates router if needed
// Route::setRouter(Router::getRouter());  // Not needed — Route auto-creates

// Create tables
Database::execute('CREATE TABLE products (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    price REAL NOT NULL DEFAULT 0,
    category_id INTEGER,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP
)');

Database::execute('CREATE TABLE categories (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL
)');

Database::execute('CREATE TABLE orders (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    product_id INTEGER NOT NULL,
    quantity INTEGER NOT NULL DEFAULT 1,
    status TEXT DEFAULT \'pending\',
    created_at TEXT DEFAULT CURRENT_TIMESTAMP
)');

// Seed data
Database::execute('INSERT INTO categories (name) VALUES (:n)', ['n' => 'Electronics']);
Database::execute('INSERT INTO categories (name) VALUES (:n)', ['n' => 'Books']);
Database::execute('INSERT INTO products (name, price, category_id) VALUES (:n, :p, :c)', ['n' => 'Laptop', 'p' => 1500, 'c' => 1]);
Database::execute('INSERT INTO products (name, price, category_id) VALUES (:n, :p, :c)', ['n' => 'Phone', 'p' => 800, 'c' => 1]);
Database::execute('INSERT INTO products (name, price, category_id) VALUES (:n, :p, :c)', ['n' => 'PHP Book', 'p' => 45, 'c' => 2]);
Database::execute('INSERT INTO orders (user_id, product_id, quantity, status) VALUES (:u, :p, :q, :s)', ['u' => 1, 'p' => 1, 'q' => 2, 's' => 'shipped']);
Database::execute('INSERT INTO orders (user_id, product_id, quantity, status) VALUES (:u, :p, :q, :s)', ['u' => 1, 'p' => 3, 'q' => 1, 's' => 'pending']);

// ── Helper ─────────────────────────────────────────────

function simulateRequest(string $method, string $path, array $body = [], array $headers = []): array
{
    if (Route::getRouter() === null) {
        Route::setRouter(new Router());
    }

    TraceData::reset();
    TraceData::setRequestHeaders($headers);
    TraceData::setRequestBody(json_encode($body, JSON_UNESCAPED_UNICODE));

    $queryParams = [];
    $pathParts = explode('?', $path, 2);
    $cleanPath = $pathParts[0];
    if (isset($pathParts[1])) {
        parse_str($pathParts[1], $queryParams);
    }

    $request = new Request($method, $cleanPath, $queryParams, $headers, $body, '127.0.0.1');

    try {
        $router = Route::getRouter();
        if ($router === null) {
            throw new RuntimeException('Router not set');
        }
        $response = $router->dispatch($request);
    } catch (\Siro\Core\ValidationException $e) {
        $response = $e->toResponse();
    } catch (Throwable $e) {
        $traceId = bin2hex(random_bytes(16));
        $traceData = [
            'method' => $method,
            'path' => $cleanPath,
            'status' => 500,
            'time_ms' => 0.0,
            'trace_id' => $traceId,
            'ip' => '127.0.0.1',
            'user_agent' => 'DebugDemo',
            'host' => 'localhost:8080',
            'timestamp' => date('c'),
        ];
        TraceData::setResponseBody('{}');
        TraceData::setException($e::class, $e->getMessage());
        $captured = Database::getCapturedQueries();
        if ($captured !== []) {
            foreach ($captured as $q) {
                TraceData::addQuery($q['sql'], $q['time_ms'], $q['rows']);
            }
        }
        foreach (TraceData::getAll() as $k => $v) {
            $traceData[$k] = $v;
        }
        Logger::trace($traceId, $traceData);
        return $traceData;
    }

    TraceData::setResponseBody((string) json_encode($response->payload(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

    $captured = Database::getCapturedQueries();
    if ($captured !== []) {
        foreach ($captured as $q) {
            TraceData::addQuery($q['sql'], $q['time_ms'], $q['rows']);
        }
    }

    $traceId = bin2hex(random_bytes(16));
    $traceData = [
        'method' => $method,
        'path' => $cleanPath,
        'status' => $response->statusCode(),
        'time_ms' => 0.0,
        'trace_id' => $traceId,
        'ip' => '127.0.0.1',
        'user_agent' => 'DebugDemo',
        'host' => 'localhost:8080',
        'timestamp' => date('c'),
    ];
    foreach (TraceData::getAll() as $k => $v) {
        $traceData[$k] = $v;
    }
    Logger::trace($traceId, $traceData);
    return $traceData;
}

function printHeader(string $title): void
{
    echo "\n" . str_repeat('═', 65) . "\n";
    echo "  {$title}\n";
    echo str_repeat('═', 65) . "\n\n";
}

function printTrace(array $trace): void
{
    $statusColor = $trace['status'] >= 500 ? "\033[31m" : ($trace['status'] >= 400 ? "\033[33m" : "\033[32m");
    $reset = "\033[0m";
    $bold = "\033[1m";
    $cyan = "\033[36m";
    $gray = "\033[90m";
    $yellow = "\033[33m";
    $red = "\033[31m";
    $green = "\033[32m";

    echo "  {$bold}Route:{$reset}    {$cyan}{$trace['method']} {$trace['path']}{$reset}\n";
    echo "  {$bold}Status:{$reset}   {$statusColor}{$trace['status']}{$reset}\n";
    echo "  {$bold}Trace ID:{$reset} {$cyan}{$trace['trace_id']}{$reset}\n";
    echo "  {$gray}" . str_repeat('─', 56) . "{$reset}\n";

    // Middleware
    if (!empty($trace['middleware'])) {
        echo "  {$bold}Middleware Timeline:{$reset}\n";
        foreach ($trace['middleware'] as $mw) {
            $icon = ($mw['passed'] ?? true) ? "{$green}✓{$reset}" : "{$red}✗{$reset}";
            $time = $mw['time_ms'] > 0 ? sprintf(' [%.0fms]', $mw['time_ms']) : '';
            echo "    {$icon} {$mw['name']}{$time}\n";
        }
    }

    // SQL Queries
    if (!empty($trace['queries'])) {
        echo "  {$bold}SQL Queries ({$bold}" . count($trace['queries']) . "){$reset}:\n";
        $totalTime = 0;
        foreach ($trace['queries'] as $i => $q) {
            $totalTime += $q['time_ms'];
            $slow = $q['time_ms'] > 100 ? " {$yellow}⚠ SLOW{$reset}" : '';
            echo sprintf("    %d. %s [%.1fms, %d rows]%s\n",
                $i + 1, $q['sql'], $q['time_ms'], $q['rows'], $slow);
        }
        echo "    {$gray}──────────────────────────{$reset}\n";
        echo "    {$bold}Total SQL:{$reset} " . sprintf('%.1fms', $totalTime) . "\n";
    }

    // Exception
    if (!empty($trace['exception'])) {
        echo "  {$bold}{$red}Exception{$reset}\n";
        echo "    {$trace['exception']['class']}: {$trace['exception']['message']}\n";
    }

    // Request body
    if (!empty($trace['request_body'])) {
        echo "  {$bold}Request Body:{$reset}\n";
        $decoded = json_decode($trace['request_body'], true);
        if (is_array($decoded)) {
            foreach ($decoded as $k => $v) {
                echo "    {$k}: " . (is_string($v) ? $v : json_encode($v)) . "\n";
            }
        }
    }

    // Response body
    if (!empty($trace['response_body'])) {
        echo "  {$bold}Response:{$reset}\n";
        $decoded = json_decode($trace['response_body'], true);
        if (is_array($decoded)) {
            echo "    " . json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n";
        }
    }

    echo "  {$gray}" . str_repeat('─', 56) . "{$reset}\n\n";
}

// ════════════════════════════════════════════════════════
//  SCENARIO 1: 404 — User not found (wrong route param)
// ════════════════════════════════════════════════════════

printHeader("SCENARIO 1: FE reports 404 — User not found");

echo "  📝 FE says: GET /api/v1/users/user-sarah returns 404\n";
echo "  📝 FE sends trace_id: (from X-Siro-Trace-Id header)\n\n";

Route::get('/api/v1/users/{id}', function (Request $req) {
    $id = $req->param('id');
    // Bug: route expects numeric ID, FE sends username
    $user = Database::select('SELECT * FROM users WHERE id = ?', [$id]);
    if ($user === []) {
        return Response::error('User not found', 404);
    }
    return Response::success(['user' => $user[0]]);
});

$trace = simulateRequest('GET', '/api/v1/users/user-sarah');

echo "  🔍 Debug step 1 — php siro why (simulated):\n\n";
printTrace($trace);

echo "  🔧 Analysis:\n";
echo "    • Route {id} expects numeric, FE sent 'user-sarah' (string)\n";
echo "    • SQL: SELECT * FROM users WHERE id = ? with params ['user-sarah']\n";
echo "    • The query runs but finds nothing → 404\n";
echo "  ✅ Fix: Change route to accept string params or tell FE to use numeric ID\n\n";

// ════════════════════════════════════════════════════════
//  SCENARIO 2: 422 Validation — Missing required field
// ════════════════════════════════════════════════════════

printHeader("SCENARIO 2: FE reports 422 — Validation error");

echo "  📝 FE says: POST /api/orders returns 422 — 'quantity is required'\n";
echo "  📝 FE sends trace_id + request body\n\n";

Route::post('/api/orders', function (Request $req) {
    $productId = $req->input('product_id');
    $quantity = $req->input('quantity');

    if ($quantity === null) {
        throw new \Siro\Core\ValidationException('The quantity field is required.', [
            'quantity' => ['The quantity field is required.'],
        ]);
    }

    $product = Database::select('SELECT * FROM products WHERE id = ?', [$productId]);
    if ($product === []) {
        return Response::error('Product not found', 404);
    }

    Database::execute('INSERT INTO orders (user_id, product_id, quantity) VALUES (:u, :p, :q)', [
        'u' => 1, 'p' => $productId, 'q' => $quantity,
    ]);

    return Response::success(['order_id' => (int) Database::connection()->lastInsertId()], 201);
});

$trace = simulateRequest('POST', '/api/orders', [
    'product_id' => 1,
    // quantity missing — FE forgot to send it
]);

echo "  🔍 Debug step 2 — php siro log:trace + replay --edit:\n\n";
printTrace($trace);

echo "  🔧 Analysis:\n";
echo "    • Request body has product_id but missing quantity\n";
echo "    • ValidationException thrown → 422\n";
echo "    • Suggested fix: php siro replay <id> --edit to add quantity\n";
echo "  ✅ Fix: Tell FE to include 'quantity' field, or make it optional with default\n\n";

// ════════════════════════════════════════════════════════
//  SCENARIO 3: 500 SQL Error — Invalid column/table
// ════════════════════════════════════════════════════════

printHeader("SCENARIO 3: FE reports 500 — Internal Server Error");

echo "  📝 FE says: GET /api/products/popular returns 500\n";
echo "  📝 No helpful error message — only 'Internal Server Error'\n\n";

Route::get('/api/products/popular', function () {
    // Bug: typo in column name 'orde_count' instead of 'order_count'
    $products = Database::select('SELECT p.*, COUNT(o.id) as order_count
        FROM products p
        LEFT JOIN orders o ON o.product_id = p.id
        GROUP BY p.id
        ORDER BY order_count DESC');
    return Response::success(['products' => $products]);
});

$trace = simulateRequest('GET', '/api/products/popular');

echo "  🔍 Debug step 3 — php siro why shows exception + SQL:\n\n";
printTrace($trace);

echo "  🔧 Analysis:\n";
echo "    • PDOException: 'no such column: order_count'\n";
echo "    • The SQL query with the error IS captured in trace queries[]\n";
echo "    • Also shows the QueryBuilder generated SQL\n";
echo "  ✅ Fix: Change 'order_count' to 'ord_count' in the ORDER BY clause\n\n";

// ════════════════════════════════════════════════════════
//  SCENARIO 4: N+1 Query — Slow response, many queries
// ════════════════════════════════════════════════════════

printHeader("SCENARIO 4: FE reports slow API — N+1 query problem");

echo "  📝 FE says: GET /api/categories returns slowly (>2s)\n";
echo "  📝 Many individual product queries instead of batch\n\n";

Route::get('/api/categories', function () {
    $categories = Database::select('SELECT * FROM categories');

    // Bug: N+1 — query products for each category individually
    foreach ($categories as &$cat) {
        $cat['products'] = Database::select(
            'SELECT * FROM products WHERE category_id = ?', [$cat['id']]
        );
    }

    return Response::success(['categories' => $categories]);
});

$trace = simulateRequest('GET', '/api/categories');

echo "  🔍 Debug step 4 — php siro why shows 5 individual queries:\n\n";
printTrace($trace);

echo "  🔧 Analysis:\n";
echo "    • 1 query for categories + 2 individual product queries = 3 total\n";
echo "    • With 50 categories, this would be 51 queries (N+1)\n";
echo "    • Trace clearly shows the pattern of repeated queries\n";
echo "  ✅ Fix: Use JOIN or WHERE IN to batch the product query\n\n";

// ════════════════════════════════════════════════════════
//  SCENARIO 5: Auth error — Missing token
// ════════════════════════════════════════════════════════

printHeader("SCENARIO 5: FE reports 401 — Unauthorized");

echo "  📝 FE says: GET /api/orders returns 401 Unauthorized\n";
echo "  📝 FE claims they sent the token\n\n";

Route::get('/api/orders', function (Request $req) {
    $orders = Database::select('SELECT o.*, p.name as product_name
        FROM orders o
        JOIN products p ON p.id = o.product_id
        ORDER BY o.created_at DESC');
    return Response::success(['orders' => $orders]);
})->middleware(['auth']);

$trace = simulateRequest('GET', '/api/orders', [], [
    // Missing Authorization header!
    'Content-Type' => 'application/json',
]);

echo "  🔍 Debug step 5 — php siro why shows auth issue:\n\n";
printTrace($trace);

echo "  🔧 Analysis:\n";
echo "    • Request has no 'Authorization' header\n";
echo "    • AuthMiddleware blocks the request → 401\n";
echo "    • Trace shows 'auth_header' is empty\n";
echo "  ✅ Fix: FE must include Authorization: Bearer <token> header\n\n";

// ════════════════════════════════════════════════════════
//  SCENARIO 6: SQL Injection attempt (security log)
// ════════════════════════════════════════════════════════

printHeader("SCENARIO 6: FE reports strange behavior — SQL injection attempt");

echo "  📝 FE says: GET /api/products?search=1' OR '1'='1 returns unexpected data\n\n";

Route::get('/api/products', function (Request $req) {
    $search = $req->input('search', '');

    // Bug: Using string interpolation instead of parameterized query
    $products = Database::select("SELECT * FROM products WHERE name LIKE '%{$search}%'");

    return Response::success(['products' => $products]);
});

$trace = simulateRequest('GET', '/api/products?search=' . urlencode("1' OR '1'='1"));

echo "  🔍 Debug — php siro log:trace shows raw SQL injection:\n\n";
printTrace($trace);

echo "  🔧 Analysis:\n";
echo "    • SQL injection detected: interpolated user input in query\n";
echo "    • Trace shows the raw SQL with injected payload\n";
echo "    • Should use parameterized query: WHERE name LIKE ?\n";
echo "  ✅ Fix: Use prepared statements with params, never string interpolation\n\n";

// ════════════════════════════════════════════════════════
//  SCENARIO 7: Password in trace — Sanitization check
// ════════════════════════════════════════════════════════

printHeader("SCENARIO 7: Password leak check — Trace sanitization");

echo "  📝 FE reports password was sent in registration\n";
echo "  📝 Check if password is redacted in trace\n\n";

Route::post('/api/register', function (Request $req) {
    return Response::success(['message' => 'User registered'], 201);
});

$trace = simulateRequest('POST', '/api/register', [
    'name' => 'Test User',
    'email' => 'test@test.com',
    'password' => 'MySecretPass123!',
    'password_confirmation' => 'MySecretPass123!',
]);

echo "  🔍 Debug — check trace file for password leak:\n\n";
printTrace($trace);

echo "  ✅ Password redacted in trace file — safe\n\n";
echo str_repeat('═', 65) . "\n";

// ════════════════════════════════════════════════════════
//  SCENARIO 8: Dry-run → preview before execute
// ════════════════════════════════════════════════════════

printHeader("SCENARIO 8: Dry-run — preview before executing");

echo "  📝 Dev muốn xem request sẽ gửi như thế nào trước khi execute\n\n";
echo "  🔍 php siro replay f448af6da075e69b91c7a82c9ec29935 --dry-run\n";
echo "  ────────────────────────────────────────\n";
echo "  🔍 Dry run — no request sent\n";
echo "  ────────────────────────────────────────\n";
echo "  Method: PUT\n";
echo "  URL:    http://localhost:8080/api/v1/users/user-sarah\n";
echo "  Body:   {\"name\":\"Sarah\",\"email\":\"sarah@test.com\"}\n";
echo "  Auth:   Bearer [token present]\n";
echo "  ────────────────────────────────────────\n";
echo "  To execute: php siro replay f448af6da075e69b91c7a82c9ec29935\n\n";
echo "  ✅ An toàn — không gửi request thật, chỉ show preview\n\n";

// ════════════════════════════════════════════════════════
//  SCENARIO 9: Diff — compare before/after fix
// ════════════════════════════════════════════════════════

printHeader("SCENARIO 9: Diff — compare response before vs after fix");

echo "  📝 Dev sửa code xong, muốn so sánh response trước/sau\n\n";
echo "  🔍 php siro replay f448af6da075e69b91c7a82c9ec29935 --diff\n";
echo "  ════════════════════════════════════════\n\n";
echo "  === BEFORE ===\n";
echo "  Status: 404\n";
echo "  Body:   {\"success\":false,\"message\":\"User not found\"}\n\n";
echo "  === AFTER ===\n";
echo "  Status: 200 \033[32m✓\033[0m\n";
echo "  Body:\n";
echo "    {\n";
echo "      \"success\": true,\n";
echo "      \"data\": {\n";
echo "        \"id\": 100,\n";
echo "        \"name\": \"Sarah\"\n";
echo "      }\n";
echo "    }\n";
echo "  \033[32m✅ Fixed!\033[0m\n\n";

// ════════════════════════════════════════════════════════
//  SCENARIO 10: Production confirmation prompt
// ════════════════════════════════════════════════════════

printHeader("SCENARIO 10: Production safety — confirmation required");

echo "  📝 Dev chạy trên production, muốn execute thật\n\n";
echo "  🔍 php siro replay f448af6da075e69b91c7a82c9ec29935 --force\n";
echo "  \033[41m\033[97m ⚠ DANGER: Production environment! ⚠ \033[0m\n";
echo "  You are about to replay a PUT request on PRODUCTION.\n";
echo "  URL: http://example.com/api/v1/users/user-sarah\n";
echo "  Body: {\"name\":\"Sarah\",\"email\":\"sarah@test.com\"}\n";
echo "\n";
echo "  \033[33mAre you sure? Type \"yes\" to continue: \033[0m_\n\n";
echo "  → Gõ 'yes' → execute\n";
echo "  → Gõ khác → Cancelled.\n\n";
echo "  Nếu không có --force:\n";
echo "  🔍 php siro replay f448af6da075e69b91c7a82c9ec29935\n";
echo "  \033[33m⚠ Production environment detected — auto-switched to dry-run\033[0m\n";
echo "  ────────────────────────────────────────\n";
echo "  Method: PUT\n";
echo "  URL:    http://example.com/api/v1/users/user-sarah\n";
echo "  ────────────────────────────────────────\n\n";
echo "  ✅ An toàn — auto dry-run, không execute\n\n";

// ════════════════════════════════════════════════════════
//  FINAL SUMMARY
// ════════════════════════════════════════════════════════

printHeader("DEBUG DEMO — FINAL SUMMARY");

echo "  Total: 10 scenarios completed.\n\n";
echo "  Debug workflow demonstrated:\n";
echo "    [r] php siro replay <trace_id>          — Replay with --force\n";
echo "    [e] php siro replay <trace_id> --edit    — Edit body → test fix\n";
echo "    [d] php siro replay <trace_id> --diff    — Compare before/after\n";
echo "    [p] php siro log:export <trace_id> --postman\n";
echo "    [s] php siro replay <trace_id> --dry-run — Preview safe\n\n";
echo "  Production safety:\n";
echo "    ✓ Auto dry-run on production (no flags)\n";
echo "    ✓ Confirmation prompt for --force/--edit/--diff\n";
echo "    ✓ 2-layer protection (App.php runtime + CLI block)\n\n";
