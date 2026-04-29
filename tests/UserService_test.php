<?php

declare(strict_types=1);

/**
 * Unit test for UserService.
 *
 * Run: php tests/UserService_test.php
 */

require_once __DIR__ . '/../vendor/autoload.php';

echo "=== UserService Unit Test ===

";

$passed = 0;
$failed = 0;

function test(string $name, callable $fn): void
{
    global $passed, $failed;
    try {
        $fn();
        echo "  \033[32m✓\033[0m {$name}
";
        $passed++;
    } catch (\Throwable $e) {
        echo "  \033[31m✗ {$name}: {$e->getMessage()}\033[0m
";
        $failed++;
    }
}

// ─── Write your tests below ────────────────────────

test('TODO: write test name', function () {
    $result = 1 + 1;
    assert($result === 2, 'Expected 2, got ' . $result);
});

echo "
=== Results ===
";
echo "Passed: {$passed}
";
echo "Failed: {$failed}
";
exit($failed > 0 ? 1 : 0);
