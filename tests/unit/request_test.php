#!/usr/bin/env php
<?php

declare(strict_types=1);

require __DIR__ . '/../../vendor/autoload.php';

use Siro\Core\Request;
use Siro\Core\ValidationException;

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

echo "Request Tests\n";
echo "=============\n\n";

$request = new Request('POST', '/test', ['page' => '2', 'search' => 'hello'], ['content-type' => 'application/json'], ['name' => 'John', 'age' => '25', 'active' => 'true', 'price' => '19.99', 'tags' => ['a', 'b']], '127.0.0.1');

test('method returns correct', function () use ($request) {
    return $request->method() === 'POST' ? true : 'expected POST';
});

test('path returns correct', function () use ($request) {
    return $request->path() === '/test' ? true : 'expected /test';
});

test('body returns all data', function () use ($request) {
    $b = $request->body();
    return isset($b['name']) && $b['name'] === 'John' ? true : 'missing name';
});

test('input returns from body', function () use ($request) {
    return $request->input('name') === 'John' ? true : 'expected John';
});

test('input returns default for missing', function () use ($request) {
    return $request->input('missing', 'default') === 'default' ? true : 'expected default';
});

test('query returns from query params', function () use ($request) {
    return $request->query('page') === '2' ? true : 'expected 2';
});

test('query returns all', function () use ($request) {
    $q = $request->query();
    return $q['page'] === '2' && $q['search'] === 'hello' ? true : 'wrong query';
});

test('int returns integer', function () use ($request) {
    return $request->int('age') === 25 ? true : 'expected 25';
});

test('int returns default for missing', function () use ($request) {
    return $request->int('missing', 99) === 99 ? true : 'expected 99';
});

test('string returns string', function () use ($request) {
    return $request->string('name') === 'John' ? true : 'expected John';
});

test('float returns float', function () use ($request) {
    return $request->float('price') === 19.99 ? true : 'expected 19.99';
});

test('bool returns true', function () use ($request) {
    return $request->bool('active') === true ? true : 'expected true';
});

test('bool returns false for missing', function () use ($request) {
    return $request->bool('missing') === false ? true : 'expected false';
});

test('array returns array', function () use ($request) {
    return $request->array('tags') === ['a', 'b'] ? true : 'expected array';
});

test('array returns default for missing', function () use ($request) {
    return $request->array('missing', ['x']) === ['x'] ? true : 'expected default';
});

test('queryInt returns integer', function () use ($request) {
    return $request->queryInt('page') === 2 ? true : 'expected 2';
});

test('queryString returns string', function () use ($request) {
    return $request->queryString('search') === 'hello' ? true : 'expected hello';
});

test('validate passes and returns data', function () use ($request) {
    $data = $request->validate(['name' => 'required']);
    return $data === ['name' => 'John'] ? true : 'expected filtered data';
});

test('validate throws on failure', function () use ($request) {
    $threw = false;
    try {
        $request->validate(['missing' => 'required']);
    } catch (ValidationException) {
        $threw = true;
    }
    return $threw ? true : 'expected exception';
});

test('validated alias works', function () use ($request) {
    $data = $request->validated(['name' => 'required']);
    return $data === ['name' => 'John'] ? true : 'expected filtered data';
});

test('only returns specified keys', function () use ($request) {
    $data = $request->only(['name', 'age']);
    return $data === ['name' => 'John', 'age' => '25'] ? true : 'wrong keys';
});

test('only skips missing keys', function () use ($request) {
    $data = $request->only(['name', 'nonexistent']);
    return $data === ['name' => 'John'] ? true : 'expected only name';
});

test('except removes specified keys', function () use ($request) {
    $data = $request->except(['name']);
    return !isset($data['name']) && isset($data['age']) ? true : 'wrong result';
});

test('except returns all if no keys', function () use ($request) {
    $data = $request->except([]);
    return count($data) === 5 ? true : 'expected all 5 fields';
});

$queryRequest = new Request('GET', '/list', ['q' => 'test', 'page' => '1'], [], [], '127.0.0.1');

test('all returns empty for GET', function () use ($queryRequest) {
    return $queryRequest->body() === [] ? true : 'expected empty';
});

test('validate on GET returns empty data', function () use ($queryRequest) {
    $data = $queryRequest->validate(['q' => 'required']);
    return $data === [] ? true : 'expected empty (q is not in body)';
});

echo "\n\nResults: {$passed} passed, {$failed} failed\n";
exit($failed > 0 ? 1 : 0);
