#!/usr/bin/env php
<?php

declare(strict_types=1);

require __DIR__ . '/../../vendor/autoload.php';

use Siro\Core\Response;

$passed = 0;
$failed = 0;

function test(string $name, callable $fn): void {
    global $passed, $failed;
    try {
        $result = $fn();
        if ($result === true) {
            echo "  PASS: {$name}\n";
            $passed++;
        } else {
            echo "  FAIL: {$name} - {$result}\n";
            $failed++;
        }
    } catch (\Throwable $e) {
        echo "  ERROR: {$name} - " . $e->getMessage() . "\n";
        $failed++;
    }
}

echo "Response Tests\n";
echo "==============\n\n";

test('success returns correct structure', function () {
    $r = Response::success(['id' => 1], 'OK');
    $p = $r->payload();
    return $p['success'] === true && $p['data'] === ['id' => 1] && $p['message'] === 'OK' ? true : 'wrong structure';
});

test('success returns 200', fn () => Response::success()->statusCode() === 200 ? true : 'expected 200');

test('error returns correct structure', function () {
    $r = Response::error('Not found', 404);
    $p = $r->payload();
    return $p['success'] === false && $p['message'] === 'Not found' ? true : 'wrong structure';
});

test('error returns given status', fn () => Response::error('bad', 422)->statusCode() === 422 ? true : 'expected 422');

test('error returns custom errors', function () {
    $r = Response::error('fail', 422, ['name' => ['Required']]);
    $p = $r->payload();
    return isset($p['meta']['errors']['name']) ? true : 'missing errors';
});

test('created returns 201', fn () => Response::created(['id' => 1])->statusCode() === 201 ? true : 'expected 201');

test('noContent returns 204', fn () => Response::noContent()->statusCode() === 204 ? true : 'expected 204');

test('paginated returns correct structure', function () {
    $r = Response::paginated([1, 2, 3], ['page' => 1, 'total' => 3]);
    $p = $r->payload();
    return $p['success'] === true && $p['data'] === [1, 2, 3] && $p['meta']['total'] === 3 ? true : 'wrong structure';
});

test('paginated returns 200', fn () => Response::paginated([], [])->statusCode() === 200 ? true : 'expected 200');

test('json payload is preserved', function () {
    $r = Response::json(['custom' => 'data'], 201);
    return $r->payload() === ['custom' => 'data'] && $r->statusCode() === 201 ? true : 'wrong structure';
});

echo "\n\nResults: {$passed} passed, {$failed} failed\n";
exit($failed > 0 ? 1 : 0);
