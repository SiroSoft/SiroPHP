<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Siro\Core\Response;
use Siro\Core\Validator;
use Siro\Core\Str;

final class SiroUnitTest extends TestCase
{
    public function testResponseSuccess(): void
    {
        $r = Response::success(['id' => 1], 'OK');
        $this->assertEquals(200, $r->statusCode());
    }

    public function testResponseError(): void
    {
        $r = Response::error('Error', 400);
        $this->assertEquals(400, $r->statusCode());
    }

    public function testResponseCreated(): void
    {
        $r = Response::created(['id' => 1], 'Created');
        $this->assertEquals(201, $r->statusCode());
    }

    public function testResponseNoContent(): void
    {
        $r = Response::noContent();
        $this->assertEquals(204, $r->statusCode());
    }

    public function testResponseJson(): void
    {
        $r = Response::json(['key' => 'val'], 200);
        $this->assertEquals(200, $r->statusCode());
    }

    public function testResponsePaginated(): void
    {
        $r = Response::paginated([], ['page' => 1], 'OK');
        $this->assertEquals(200, $r->statusCode());
    }

    public function testValidatorRequiredPass(): void
    {
        $errors = Validator::make(['f' => 'v'], ['f' => 'required']);
        $this->assertEmpty($errors);
    }

    public function testValidatorRequiredFail(): void
    {
        $errors = Validator::make(['f' => ''], ['f' => 'required']);
        $this->assertNotEmpty($errors);
    }

    public function testValidatorEmailPass(): void
    {
        $errors = Validator::make(['e' => 'a@b.c'], ['e' => 'email']);
        $this->assertEmpty($errors);
    }

    public function testValidatorEmailFail(): void
    {
        $errors = Validator::make(['e' => 'bad'], ['e' => 'email']);
        $this->assertNotEmpty($errors);
    }

    public function testValidatorMinPass(): void
    {
        $errors = Validator::make(['p' => '123456'], ['p' => 'min:6']);
        $this->assertEmpty($errors);
    }

    public function testValidatorMinFail(): void
    {
        $errors = Validator::make(['p' => '12'], ['p' => 'min:6']);
        $this->assertNotEmpty($errors);
    }

    public function testValidatorMaxPass(): void
    {
        $errors = Validator::make(['n' => 'ab'], ['n' => 'max:100']);
        $this->assertEmpty($errors);
    }

    public function testValidatorMaxFail(): void
    {
        $errors = Validator::make(['n' => str_repeat('a', 101)], ['n' => 'max:100']);
        $this->assertNotEmpty($errors);
    }

    public function testValidatorIntegerPass(): void
    {
        $errors = Validator::make(['a' => '25'], ['a' => 'integer']);
        $this->assertEmpty($errors);
    }

    public function testValidatorIntegerFail(): void
    {
        $errors = Validator::make(['a' => 'abc'], ['a' => 'integer']);
        $this->assertNotEmpty($errors);
    }

    public function testStrRandomLength(): void
    {
        $result = Str::random(16);
        $this->assertEquals(16, strlen($result));
    }

    public function testStrRandomCharacters(): void
    {
        $result = Str::random(32);
        $this->assertMatchesRegularExpression('/^[a-zA-Z0-9]+$/', $result);
    }

    public function testStrSlug(): void
    {
        $result = Str::slug('Hello World');
        $this->assertEquals('hello-world', $result);
    }

    public function testStrSlugWithSpecialChars(): void
    {
        $result = Str::slug('Hello World! @#$');
        $this->assertStringNotContainsString('!', $result);
    }

    public function testStrLimit(): void
    {
        $result = Str::limit('Hello World', 5);
        $this->assertEquals('He...', $result);
    }

    public function testStrLimitShorter(): void
    {
        $result = Str::limit('Hello', 10);
        $this->assertEquals('Hello', $result);
    }

    public function testValidatorInPass(): void
    {
        $errors = Validator::make(['s' => 'a'], ['s' => 'in:a,b,c']);
        $this->assertEmpty($errors);
    }

    public function testValidatorInFail(): void
    {
        $errors = Validator::make(['s' => 'd'], ['s' => 'in:a,b,c']);
        $this->assertNotEmpty($errors);
    }

    public function testValidatorNumericPass(): void
    {
        $errors = Validator::make(['p' => '99.9'], ['p' => 'numeric']);
        $this->assertEmpty($errors);
    }

    public function testValidatorNumericFail(): void
    {
        $errors = Validator::make(['p' => 'abc'], ['p' => 'numeric']);
        $this->assertNotEmpty($errors);
    }
}