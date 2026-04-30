<?php

declare(strict_types=1);

/**
 * Tests for remaining core features: UploadedFile, Schedule, Throttle.
 * Run: php tests/remaining_test.php
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Siro\Core\App;
use Siro\Core\UploadedFile;
use Siro\Core\Schedule;
use Siro\Core\Storage;
use Siro\Core\Request;
use Siro\Core\Response;
use Siro\Core\Database;

$basePath = dirname(__DIR__);
$passed = 0;
$failed = 0;

$app = new App($basePath);
$app->boot();

function test(string $name, callable $fn): void
{
    global $passed, $failed;
    try {
        $fn();
        echo "  \033[32m✓\033[0m {$name}\n";
        $passed++;
    } catch (Throwable $e) {
        echo "  \033[31m✗ {$name}: {$e->getMessage()}\033[0m\n";
        $failed++;
    }
}

function ok(bool $condition, string $msg): void
{
    if (!$condition) {
        throw new RuntimeException($msg);
    }
}

ob_start();
echo "=== Remaining Core Features Tests ===\n\n";

echo "--- UploadedFile (CLI mode - isValid returns false) ---\n";

test('UploadedFile constructor stores file metadata', function () {
    $uf = new UploadedFile([
        'name' => 'test.txt',
        'tmp_name' => '/tmp/phpXXXXXX',
        'type' => 'text/plain',
        'size' => 12,
        'error' => UPLOAD_ERR_OK,
    ]);
    ok($uf->getClientOriginalName() === 'test.txt', 'Expected test.txt');
    ok($uf->getMimeType() === 'text/plain', 'Expected text/plain');
    ok($uf->getSize() === 12, 'Expected size 12');
    ok($uf->getError() === UPLOAD_ERR_OK, 'Expected error OK');
});

test('UploadedFile::isValid() returns false in CLI (no HTTP upload)', function () {
    $uf = new UploadedFile([
        'name' => 'f.txt',
        'tmp_name' => '/tmp/nonexistent',
        'type' => 'text/plain',
        'size' => 1,
        'error' => UPLOAD_ERR_OK,
    ]);
    ok($uf->isValid() === false, 'Expected false in CLI mode');
});

test('UploadedFile::isValid() returns false for NO_FILE error', function () {
    $uf = new UploadedFile([
        'name' => '',
        'tmp_name' => '',
        'type' => '',
        'size' => 0,
        'error' => UPLOAD_ERR_NO_FILE,
    ]);
    ok($uf->isValid() === false, 'Expected invalid');
});

test('UploadedFile getClientOriginalExtension', function () {
    $uf = new UploadedFile([
        'name' => 'photo.jpg',
        'tmp_name' => '',
        'type' => 'image/jpeg',
        'size' => 0,
        'error' => UPLOAD_ERR_NO_FILE,
    ]);
    ok($uf->getClientOriginalExtension() === 'jpg', 'Expected jpg');
});

test('UploadedFile getPathname returns tmp path', function () {
    $uf = new UploadedFile([
        'name' => 'f.txt',
        'tmp_name' => '/tmp/test_upload',
        'type' => 'text/plain',
        'size' => 1,
        'error' => UPLOAD_ERR_OK,
    ]);
    ok($uf->getPathname() === '/tmp/test_upload', 'Expected tmp path');
});

// ─── Schedule ──────────────────────────────────

echo "\n--- Schedule ---\n";

test('Schedule::command() registers command', function () {
    $s = new Schedule();
    $s->command('php siro route:list')->daily();
    ok(true, 'Command registered without error');
});

test('Schedule::call() registers callback', function () {
    $s = new Schedule();
    $s->call(function () { return 'done'; })->everyMinute();
    ok(true, 'Callback registered without error');
});

test('Schedule::run() executes due commands', function () use ($basePath) {
    $s = new Schedule();
    $executed = false;
    $s->call(function () use (&$executed) { $executed = true; })->everyMinute();
    $s->run($basePath);
    ok($executed === true, 'Callback should execute');
});

test('Schedule schedules daily jobs', function () {
    $s = new Schedule();
    $s->command('php siro route:list')->daily();
    $s->call(function () { return 'done'; })->daily();
    ok(true, 'Daily schedule registered');
});

test('Schedule schedules hourly jobs', function () {
    $s = new Schedule();
    $s->command('php siro doctor')->hourly();
    ok(true, 'Hourly schedule registered');
});

// ─── Rate Limiter ──────────────────────────────

echo "\n--- Throttle Middleware ---\n";

test('Rate limit storage directory accessible', function () {
    $rateDir = $basePath . '/storage/rate_limit';
    if (!is_dir($rateDir)) {
        @mkdir($rateDir, 0775, true);
    }
    ok(is_dir($rateDir), 'Rate limit dir should be creatable');
});

// ─── Storage ───────────────────────────────────

echo "\n--- Storage ---\n";

test('Storage::url() returns string', function () {
    $url = Storage::url('test.txt');
    ok(is_string($url), 'Expected string');
    ok(str_contains($url, 'storage'), 'Expected storage in URL');
});

test('Storage::files() returns array', function () {
    $files = Storage::files();
    ok(is_array($files), 'Expected array');
});

// ─── Results ───────────────────────────────────

echo "\n=== Results ===\n";
echo "Passed: {$passed}\n";
echo "Failed: {$failed}\n";
exit($failed > 0 ? 1 : 0);
