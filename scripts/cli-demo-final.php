<?php

/**
 * Run ALL CLI debug commands on real generated traces.
 * 
 * Usage: php scripts/live-debug-test.php   (first — generates traces)
 *        php scripts/cli-demo-final.php    (then — run CLI debug commands)
 */

require __DIR__ . '/../vendor/autoload.php';

$bp = dirname(__DIR__);
putenv('APP_DEBUG=true');
putenv('APP_ENV=local');

function run(string $label, string $commandClass, array $args): void
{
    global $bp;
    echo "\n" . str_repeat('═', 65) . "\n";
    echo "  \033[1m{$label}\033[0m\n";
    echo str_repeat('═', 65) . "\n";
    $cmd = new $commandClass($bp);
    $cmd->run($args);
}

// 1. php siro why — show last error
run('php siro why', Siro\Core\Commands\DebugLastCommand::class, []);

// 2. php siro traces — list all
run('php siro traces --limit=10', Siro\Core\Commands\TraceListCommand::class, ['--limit=10']);

// 3. php siro log:trace --status=500 — filter errors
run('php siro log:trace --status=500', Siro\Core\Commands\LogTraceCommand::class, ['--status=500', '--limit=5']);

// 4. Find and show a specific trace by path
$tracesDir = $bp . '/storage/logs/traces';
$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($tracesDir, RecursiveDirectoryIterator::SKIP_DOTS));
$found404 = null;
$foundError = null;
foreach ($it as $f) {
    if ($f->getExtension() !== 'json') continue;
    $d = json_decode(file_get_contents((string) $f), true);
    if (!is_array($d)) continue;
    if (($d['path'] ?? '') === '/api/products/9999') $found404 = $d;
    if (($d['path'] ?? '') === '/api/error-sql') $foundError = $d;
}

if ($found404) {
    run("php siro log:trace {$found404['trace_id']} (404)", Siro\Core\Commands\LogTraceCommand::class, [$found404['trace_id']]);
}

if ($foundError) {
    run("php siro log:trace {$foundError['trace_id']} (SQL error)", Siro\Core\Commands\LogTraceCommand::class, [$foundError['trace_id']]);
    run("php siro replay {$foundError['trace_id']} --dry-run", Siro\Core\Commands\LogReplayCommand::class, [$foundError['trace_id'], '--dry-run']);
}

echo "\n" . str_repeat('═', 65) . "\n";
echo "  \033[1mDONE — All CLI debug commands executed\033[0m\n";
echo str_repeat('═', 65) . "\n\n";
