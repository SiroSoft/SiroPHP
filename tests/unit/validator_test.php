#!/usr/bin/env php
<?php

declare(strict_types=1);

require __DIR__ . '/../../vendor/autoload.php';

use Siro\Core\Validator;

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

echo "Validator Tests\n";
echo "==============\n\n";

// Basic rules
test('required passes with value', fn () => Validator::make(['name' => 'John'], ['name' => 'required']) === [] ? true : 'expected no errors');
test('required fails with null', fn () => isset(Validator::make(['name' => null], ['name' => 'required'])['name']) ? true : 'expected error');
test('required fails with empty string', fn () => isset(Validator::make(['name' => ''], ['name' => 'required'])['name']) ? true : 'expected error');
test('email passes valid', fn () => Validator::make(['email' => 'a@b.com'], ['email' => 'email']) === [] ? true : 'expected no errors');
test('email fails invalid', fn () => isset(Validator::make(['email' => 'not-email'], ['email' => 'email'])['email']) ? true : 'expected error');
test('numeric passes', fn () => Validator::make(['val' => '42'], ['val' => 'numeric']) === [] ? true : 'expected no errors');
test('numeric fails', fn () => isset(Validator::make(['val' => 'abc'], ['val' => 'numeric'])['val']) ? true : 'expected error');
test('integer passes', fn () => Validator::make(['val' => 42], ['val' => 'integer']) === [] ? true : 'expected no errors');
test('integer fails', fn () => isset(Validator::make(['val' => 4.2], ['val' => 'integer'])['val']) ? true : 'expected error');
test('min passes string', fn () => Validator::make(['val' => 'hello'], ['val' => 'min:3']) === [] ? true : 'expected no errors');
test('min fails string', fn () => isset(Validator::make(['val' => 'ab'], ['val' => 'min:3'])['val']) ? true : 'expected error');
test('max passes string', fn () => Validator::make(['val' => 'ab'], ['val' => 'max:3']) === [] ? true : 'expected no errors');
test('max fails string', fn () => isset(Validator::make(['val' => 'abcd'], ['val' => 'max:3'])['val']) ? true : 'expected error');

echo "\nNew Rules\n";
echo "----------\n";

// confirmed
test('confirmed passes', fn () => Validator::make(['pwd' => 'secret', 'pwd_confirmation' => 'secret'], ['pwd' => 'confirmed']) === [] ? true : 'expected no errors');
test('confirmed fails mismatch', fn () => isset(Validator::make(['pwd' => 'secret', 'pwd_confirmation' => 'different'], ['pwd' => 'confirmed'])['pwd']) ? true : 'expected error');
test('confirmed fails missing confirmation', fn () => isset(Validator::make(['pwd' => 'secret'], ['pwd' => 'confirmed'])['pwd']) ? true : 'expected error');

// in
test('in passes', fn () => Validator::make(['status' => 'active'], ['status' => 'in:active,inactive']) === [] ? true : 'expected no errors');
test('in fails', fn () => isset(Validator::make(['status' => 'deleted'], ['status' => 'in:active,inactive'])['status']) ? true : 'expected error');

// non-required empty field should be skipped
test('non-required empty passes', fn () => Validator::make(['name' => ''], ['email' => 'email']) === [] ? true : 'expected no errors');

echo "\n\nResults: {$passed} passed, {$failed} failed\n";
exit($failed > 0 ? 1 : 0);
