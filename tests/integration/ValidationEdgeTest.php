<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Tests\TestCase;
use Siro\Core\Response;
use Siro\Core\Validator;

final class ValidationEdgeTest extends TestCase
{
    public function testValidateRequiredPasses(): void
    {
        $errors = Validator::make(['name' => 'John'], ['name' => 'required']);
        $this->assertEmpty($errors);
    }

    public function testValidateRequiredFails(): void
    {
        $errors = Validator::make(['name' => ''], ['name' => 'required']);
        $this->assertNotEmpty($errors);
    }

    public function testValidateEmailPasses(): void
    {
        $errors = Validator::make(['email' => 'a@b.com'], ['email' => 'email']);
        $this->assertEmpty($errors);
    }

    public function testValidateEmailFails(): void
    {
        $errors = Validator::make(['email' => 'not-email'], ['email' => 'email']);
        $this->assertNotEmpty($errors);
    }

    public function testValidateMinPasses(): void
    {
        $errors = Validator::make(['pass' => '123456'], ['pass' => 'min:6']);
        $this->assertEmpty($errors);
    }

    public function testValidateMinFails(): void
    {
        $errors = Validator::make(['pass' => '12'], ['pass' => 'min:6']);
        $this->assertNotEmpty($errors);
    }

    public function testValidateMaxPasses(): void
    {
        $errors = Validator::make(['name' => 'ab'], ['name' => 'max:100']);
        $this->assertEmpty($errors);
    }

    public function testValidateMaxFails(): void
    {
        $errors = Validator::make(['name' => str_repeat('a', 101)], ['name' => 'max:100']);
        $this->assertNotEmpty($errors);
    }

    public function testValidateIntegerPasses(): void
    {
        $errors = Validator::make(['age' => '25'], ['age' => 'integer']);
        $this->assertEmpty($errors);
    }

    public function testValidateIntegerFails(): void
    {
        $errors = Validator::make(['age' => 'abc'], ['age' => 'integer']);
        $this->assertNotEmpty($errors);
    }

    public function testValidateNumericPasses(): void
    {
        $errors = Validator::make(['price' => '99.99'], ['price' => 'numeric']);
        $this->assertEmpty($errors);
    }

    public function testValidateNumericFails(): void
    {
        $errors = Validator::make(['price' => 'abc'], ['price' => 'numeric']);
        $this->assertNotEmpty($errors);
    }

    public function testValidateInPasses(): void
    {
        $errors = Validator::make(['status' => 'active'], ['status' => 'in:active,inactive']);
        $this->assertEmpty($errors);
    }

    public function testValidateInFails(): void
    {
        $errors = Validator::make(['status' => 'banned'], ['status' => 'in:active,inactive']);
        $this->assertNotEmpty($errors);
    }

    public function testValidateMultipleFieldsAllFail(): void
    {
        $data = ['name' => '', 'email' => 'bad', 'age' => ''];
        $rules = ['name' => 'required', 'email' => 'email', 'age' => 'required|integer'];
        $errors = Validator::make($data, $rules);
        $this->assertCount(3, $errors);
    }

    public function testValidateMultipleFieldsAllPass(): void
    {
        $data = ['name' => 'John', 'email' => 'john@test.com', 'age' => '30'];
        $rules = ['name' => 'required|min:2', 'email' => 'email', 'age' => 'required|integer'];
        $errors = Validator::make($data, $rules);
        $this->assertEmpty($errors);
    }

    public function testResponseSuccessPayload(): void
    {
        $r = Response::success(['key' => 'val'], 'OK');
        $p = $r->payload();
        $this->assertTrue($p['success']);
        $this->assertEquals('OK', $p['message']);
        $this->assertEquals(['key' => 'val'], $p['data']);
    }

    public function testResponseErrorPayload(): void
    {
        $r = Response::error('Error msg', 400);
        $p = $r->payload();
        $this->assertFalse($p['success']);
        $this->assertEquals('Error msg', $p['message']);
    }

    public function testResponseJsonPayload(): void
    {
        $r = Response::json(['custom' => 'data'], 201);
        $this->assertEquals(201, $r->statusCode());
    }

    public function testResponseCreatedPayload(): void
    {
        $r = Response::created(['id' => 1], 'Created');
        $this->assertEquals(201, $r->statusCode());
    }

    public function testResponseNoContentHasNoPayload(): void
    {
        $r = Response::noContent();
        $this->assertEquals(204, $r->statusCode());
    }

    public function testResponsePaginatedPayload(): void
    {
        $r = Response::paginated([], ['page' => 1, 'per_page' => 15, 'total' => 0, 'last_page' => 0], 'OK');
        $p = $r->payload();
        $this->assertTrue($p['success']);
        $this->assertArrayHasKey('meta', $p);
    }

    public function testSiroDebugStaticMethodsExist(): void
    {
        $this->assertTrue(method_exists(Response::class, 'enableDebug')); // @phpstan-ignore function.alreadyNarrowedType
        $this->assertTrue(method_exists(Response::class, 'setDebugMeta'));
    }

    public function testValidatorMakeReturnsArray(): void
    {
        $result = Validator::make(['test' => 'val'], ['test' => 'required']);
        $this->assertIsArray($result); // @phpstan-ignore method.alreadyNarrowedType
    }

    public function testValidatorExtendWorks(): void
    {
        Validator::extend('even', function ($v) {
            return ((int)$v) % 2 === 0 ? true : ':field must be even';
        });
        $errors = Validator::make(['num' => '3'], ['num' => 'even']);
        $this->assertArrayHasKey('num', $errors);
    }
}