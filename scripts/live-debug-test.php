<?php

/**
 * Live HTTP test → generates real trace files → debug with CLI tools
 * 
 * Usage: php scripts/live-debug-test.php
 * Then:   php siro why
 *         php siro traces
 *         php siro replay <trace_id> --dry-run
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Siro\Core\App;
use Siro\Core\Database;
use Siro\Core\Debug\TraceData;
use Siro\Core\Logger;
use Siro\Core\Request;
use Siro\Core\Route;
use Siro\Core\Router;
use Siro\Core\Response;

$basePath = dirname(__DIR__);
putenv('APP_DEBUG=true');
putenv('APP_ENV=local');
putenv('SIRO_BASE_PATH=' . $basePath);

// Boot full app
Logger::boot($basePath);
Database::configure([
    'driver' => 'sqlite',
    'database' => ':memory:',
    'capture_queries' => true,
], 'default');

Router::setMiddlewareAliases([
    'auth' => \App\Middleware\AuthMiddleware::class,
    'json' => \Siro\Core\Middleware\JsonMiddleware::class,
]);
if (Route::getRouter() === null) Route::setRouter(new Router());

// Create tables
Database::execute('CREATE TABLE products (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT, price REAL, category_id INTEGER)');
Database::execute('CREATE TABLE categories (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT)');
Database::execute('INSERT INTO categories (name) VALUES (:n)', ['n' => 'Electronics']);
Database::execute('INSERT INTO categories (name) VALUES (:n)', ['n' => 'Books']);
Database::execute('INSERT INTO products (name, price, category_id) VALUES (:n, :p, :c)', ['n' => 'Laptop', 'p' => 1500, 'c' => 1]);
Database::execute('INSERT INTO products (name, price, category_id) VALUES (:n, :p, :c)', ['n' => 'Phone', 'p' => 800, 'c' => 1]);
Database::execute('INSERT INTO products (name, price, category_id) VALUES (:n, :p, :c)', ['n' => 'PHP Book', 'p' => 45, 'c' => 2]);

// Register routes (simulate api.php)
Route::get('/health/live', fn() => ['success' => true, 'message' => 'OK']);
Route::get('/api/products', function () {
    return ['success' => true, 'data' => Database::select('SELECT * FROM products ORDER BY id')];
});
Route::get('/api/products/{id}', function (Request $req) {
    $rows = Database::select('SELECT * FROM products WHERE id = ?', [(int) $req->param('id')]);
    if (!$rows) return Response::error('Product not found', 404);
    return ['success' => true, 'data' => $rows[0]];
});
Route::post('/api/products', function (Request $req) {
    $name = $req->input('name');
    if (!$name) throw new \Siro\Core\ValidationException('Name required', ['name' => ['Required']]);
    $price = (float) $req->input('price', 0);
    Database::execute('INSERT INTO products (name, price) VALUES (:n, :p)', ['n' => $name, 'p' => $price]);
    return Response::success(['id' => (int) Database::connection()->lastInsertId()], 201);
})->middleware(['json']);
Route::get('/api/categories', function () {
    $cats = Database::select('SELECT * FROM categories');
    foreach ($cats as &$c) {
        $c['products'] = Database::select('SELECT * FROM products WHERE category_id = ?', [$c['id']]);
    }
    return ['success' => true, 'data' => $cats];
});
Route::get('/api/error-sql', function () {
    Database::select('SELECT * FROM nonexistent_table');
    return ['ok' => true];
});

// ─── Simulate HTTP requests via App → generates real traces ───

function http(string $method, string $path, array $body = [], array $headers = []): void
{
    TraceData::reset();
    TraceData::setRequestHeaders($headers);
    TraceData::setRequestBody(json_encode($body));

    $parts = explode('?', $path, 2);
    $cleanPath = $parts[0];
    $qp = [];
    if (isset($parts[1])) parse_str($parts[1], $qp);

    $req = new Request($method, $cleanPath, $qp, $headers, $body, '127.0.0.1');
    $router = Route::getRouter();

    try {
        $resp = $router->dispatch($req);
        $status = $resp->statusCode();
        TraceData::setResponseBody((string) json_encode($resp->payload()));
    } catch (\Siro\Core\ValidationException $e) {
        $resp = $e->toResponse();
        $status = $resp->statusCode();
        TraceData::setResponseBody((string) json_encode($resp->payload()));
        TraceData::setException($e::class, $e->getMessage());
    } catch (\Throwable $e) {
        $status = 500;
        TraceData::setResponseBody('{}');
        TraceData::setException($e::class, $e->getMessage());
    }

    $captured = Database::getCapturedQueries();
    if ($captured !== []) {
        foreach ($captured as $q) {
            TraceData::addQuery($q['sql'], $q['time_ms'], $q['rows']);
        }
    }

    $traceId = bin2hex(random_bytes(8));
    $traceData = [
        'method' => $method, 'path' => $cleanPath,
        'status' => $status, 'time_ms' => round((microtime(true) - ($GLOBALS['_start'] ?? microtime(true))) * 1000, 2),
        'trace_id' => $traceId, 'ip' => '127.0.0.1',
        'user_agent' => 'LiveTest', 'host' => 'localhost:8080', 'timestamp' => date('c'),
    ];
    foreach (TraceData::getAll() as $k => $v) $traceData[$k] = $v;
    Logger::trace($traceId, $traceData);

    echo "  {$method} {$path} → {$status} [trace: {$traceId}]\n";
    // Store latest trace IDs for CLI testing
    global $traceIds;
    $traceIds[] = $traceId;
}

$GLOBALS['_start'] = microtime(true);
$traceIds = [];

echo "\n═══════════════════════════════════════════════════════\n";
echo "  LIVE DEBUG TEST — Generating real traces\n";
echo "═══════════════════════════════════════════════════════\n\n";

echo "─── Making requests ───────────────────────────────\n\n";
http('GET', '/health/live');
http('GET', '/api/products');
http('GET', '/api/products/1');
http('GET', '/api/products/9999');          // 404
http('POST', '/api/products', ['name' => 'Tablet', 'price' => 300]);
http('POST', '/api/products', []);           // 422 (missing name)
http('GET', '/api/categories');              // N+1 pattern
http('GET', '/api/error-sql');               // 500 SQL error

echo "\n─── Traces generated ───────────────────────────────\n";
echo "  " . count($traceIds) . " traces written to storage/logs/traces/\n\n";
echo "  Now run these CLI commands:\n";
echo "    php siro why          — shows latest trace\n";
echo "    php siro traces       — list all traces\n";
echo "    php siro log:trace --status=500  — filter errors\n\n";

// Print last trace for each type
echo "─── Latest traces summary ─────────────────────────\n";
echo str_pad('Trace ID', 20) . ' ' . str_pad('Method', 8) . ' Status  Path' . "\n";
echo str_repeat('-', 65) . "\n";
foreach (array_slice($traceIds, -5) as $id) {
    $file = null;
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(
        $basePath . '/storage/logs/traces', RecursiveDirectoryIterator::SKIP_DOTS
    ));
    foreach ($it as $f) {
        if ($f->getExtension() === 'json' && $f->getBasename('.json') === $id) {
            $file = (string) $f;
            break;
        }
    }
    if (!$file) continue;
    $d = json_decode(file_get_contents($file), true);
    echo str_pad(substr($id, 0, 16), 20) . ' '
        . str_pad($d['method'] ?? '?', 8) . ' '
        . ($d['status'] ?? '?') . '     '
        . ($d['path'] ?? '?') . "\n";
}
echo str_repeat('═', 65) . "\n\n";
