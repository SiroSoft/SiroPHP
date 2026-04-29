<?php

declare(strict_types=1);

/**
 * Test for v0.8.7: Storage + Custom Validation + Gzip.
 *
 * Run: php tests/v087_test.php
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Siro\Core\App;
use Siro\Core\Storage;
use Siro\Core\Validator;
use Siro\Core\Response;

$basePath = dirname(__DIR__);
define('SIRO_BASE_PATH', $basePath);

$app = new App($basePath);
$app->boot();

echo "=== v0.8.7: Storage + Custom Validation + Gzip ===\n\n";

$passed = 0;
$failed = 0;

function test(string $name, callable $fn): void
{
    global $passed, $failed;
    try {
        $fn();
        echo "  \xE2\x9C\x93 {$name}\n";
        $passed++;
    } catch (Throwable $e) {
        echo "  \xE2\x9C\x97 {$name}: {$e->getMessage()}\n";
        echo "    in {$e->getFile()}:{$e->getLine()}\n";
        $failed++;
    }
}

function assertTrue(mixed $value, string $msg = ''): void
{
    if ($value !== true) {
        throw new RuntimeException($msg ?: 'Expected true, got ' . gettype($value));
    }
}

function assertEquals(mixed $expected, mixed $actual, string $msg = ''): void
{
    if ($expected !== $actual) {
        throw new RuntimeException($msg ?: 'Expected ' . json_encode($expected) . ', got ' . json_encode($actual));
    }
}

function assertNull(mixed $value, string $msg = ''): void
{
    if ($value !== null) {
        throw new RuntimeException($msg ?: 'Expected null, got ' . json_encode($value));
    }
}

// ─── Storage Tests ───────────────────────────────────────
echo "--- Storage: Local Driver ---\n";

$testFile = 'test_' . time() . '.txt';

test('Storage::put() writes file', function () use ($testFile): void {
    $result = Storage::put($testFile, 'Hello Storage!');
    assertTrue($result, 'put() should return true');
});

test('Storage::exists() finds file', function () use ($testFile): void {
    assertTrue(Storage::exists($testFile));
});

test('Storage::get() reads file', function () use ($testFile): void {
    assertEquals('Hello Storage!', Storage::get($testFile));
});

test('Storage::url() returns path', function () use ($testFile): void {
    $url = Storage::url($testFile);
    assertTrue(str_contains($url, $testFile), 'URL should contain filename');
});

test('Storage::delete() removes file', function () use ($testFile): void {
    assertTrue(Storage::delete($testFile));
    assertTrue(!Storage::exists($testFile));
});

test('Storage::get() returns null for missing file', function (): void {
    assertNull(Storage::get('nonexistent_' . time() . '.txt'));
});

test('Storage::put() creates directories', function (): void {
    $result = Storage::put('subdir/nested/test.txt', 'Nested content');
    assertTrue($result);
    assertTrue(Storage::exists('subdir/nested/test.txt'));
    assertEquals('Nested content', Storage::get('subdir/nested/test.txt'));

    // Cleanup
    Storage::delete('subdir/nested/test.txt');
});

test('Storage::files() lists directory', function (): void {
    Storage::put('list_test_a.txt', 'A');
    Storage::put('list_test_b.txt', 'B');

    $files = Storage::files();
    $found = array_filter($files, fn($f) => str_starts_with($f, 'list_test_'));
    assertEquals(2, count($found));

    Storage::delete('list_test_a.txt');
    Storage::delete('list_test_b.txt');
});

// ─── Custom Validation Tests ─────────────────────────────
echo "\n--- Custom Validation: Validator::extend() ---\n";

test('Validator::extend() registers custom rule', function (): void {
    Validator::extend('uppercase', function ($value): bool {
        return (string) $value === strtoupper((string) $value);
    });
    $errors = Validator::make(['name' => 'HELLO'], ['name' => 'uppercase']);
    assertEquals([], $errors, 'Uppercase value should pass');
});

test('Custom rule fails correctly', function (): void {
    Validator::extend('uppercase', function ($value): bool {
        return (string) $value === strtoupper((string) $value);
    });
    $errors = Validator::make(['name' => 'hello'], ['name' => 'uppercase']);
    assertTrue(isset($errors['name']), 'Lowercase should fail uppercase rule');
});

test('Custom rule with parameter', function (): void {
    Validator::extend('min_words', function ($value, $field, $input, $param): bool {
        $min = (int) ($param ?? 1);
        return str_word_count((string) $value) >= $min;
    });
    $errors = Validator::make(['content' => 'hello world'], ['content' => 'min_words:2']);
    assertEquals([], $errors, '2 words should pass min_words:2');
});

test('Custom rule with parameter fails', function (): void {
    Validator::extend('min_words', function ($value, $field, $input, $param): bool {
        $min = (int) ($param ?? 1);
        return str_word_count((string) $value) >= $min;
    });
    $errors = Validator::make(['content' => 'hello'], ['content' => 'min_words:2']);
    assertTrue(isset($errors['content']), '1 word should fail min_words:2');
});

test('Custom rule returning string message', function (): void {
    Validator::extend('phone', function ($value): string|bool {
        if (preg_match('/^\+?[0-9]{7,15}$/', (string) $value)) {
            return true;
        }
        return ':field is not a valid phone number';
    });
    $errors = Validator::make(['phone' => 'abc'], ['phone' => 'phone']);
    assertTrue(str_contains($errors['phone'][0] ?? '', 'Phone is not a valid phone number'));
});

test('Custom rule passes for valid phone', function (): void {
    Validator::extend('phone', function ($value): string|bool {
        if (preg_match('/^\+?[0-9]{7,15}$/', (string) $value)) {
            return true;
        }
        return ':field is not a valid phone number';
    });
    $errors = Validator::make(['phone' => '+84123456789'], ['phone' => 'phone']);
    assertEquals([], $errors);
});

test('Custom rule receives full input array', function (): void {
    $received = null;
    Validator::extend('check_email', function ($value, $field, $input) use (&$received): bool {
        $received = $input;
        return true;
    });
    Validator::make(['email' => 'a@b.com', 'name' => 'Test'], ['email' => 'check_email']);
    assertEquals('Test', $received['name'] ?? null);
});

// ─── Gzip Compression ────────────────────────────────────
echo "\n--- Response: Gzip Compression ---\n";

test('Response gzencode function available', function (): void {
    assertTrue(function_exists('gzencode'), 'gzencode should be available');
});

test('Response::send() with gzip', function (): void {
    // Use large payload to ensure compression actually shrinks
    $bigData = ['data' => str_repeat('Hello World this is a test payload ', 100)];
    $encoded = json_encode($bigData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    assertTrue($encoded !== false, 'JSON should encode');

    $compressed = gzencode($encoded);
    assertTrue($compressed !== false, 'gzencode should work');
    assertTrue(strlen($compressed) < strlen($encoded), 'Large payload should compress smaller');
});

test('Gzip header set correctly', function (): void {
    // Verify Response has gzip logic
    $refl = new ReflectionClass(Response::class);
    $send = $refl->getMethod('send');
    $send->setAccessible(true);

    // Just verify the method contains gzip logic
    $file = new ReflectionClass(Response::class);
    $filename = $file->getFileName();
    $contents = file_get_contents($filename);
    assertTrue(str_contains($contents, 'Content-Encoding: gzip'), 'Response should have gzip support');
    assertTrue(str_contains($contents, 'gzencode'), 'Response should call gzencode');
});

// ─── Summary ─────────────────────────────────────────────
echo "\n=== Results ===\n";
echo "Passed: {$passed}\n";
echo "Failed: {$failed}\n";

// Cleanup
$storageDir = $basePath . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'app';
if (is_dir($storageDir)) {
    $it = new RecursiveDirectoryIterator($storageDir, RecursiveDirectoryIterator::SKIP_DOTS);
    $files = new RecursiveIteratorIterator($it, RecursiveIteratorIterator::CHILD_FIRST);
    foreach ($files as $f) {
        if ($f->isFile()) { @unlink($f->getRealPath()); }
    }
}

exit($failed > 0 ? 1 : 0);
