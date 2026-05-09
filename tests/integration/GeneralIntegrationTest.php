<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Tests\TestCase;
use Siro\Core\App;
use Siro\Core\Request;
use Siro\Core\Response;
use Siro\Core\Router;
use Siro\Core\Cache;
use Siro\Core\Storage;
use Siro\Core\Validator;
use Siro\Core\ValidationException;

final class GeneralIntegrationTest extends TestCase
{
    private App $app;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app = $this->createApp();
    }

    public function testAppBootsWithoutError(): void
    {
        $this->assertNotNull($this->app);
    }

    public function testRouterDispatchesRootEndpoint(): void
    {
        $response = $this->dispatch($this->app, 'GET', '/');
        $this->assertEquals(200, $response->statusCode());
        $payload = $response->payload();
        $this->assertTrue($payload['success']);
        $this->assertArrayHasKey('version', $payload['data']);
    }

    public function testRouterReturns404ForUnknownRoute(): void
    {
        $response = $this->dispatch($this->app, 'GET', '/nonexistent');
        $this->assertEquals(404, $response->statusCode());
        $this->assertFalse($response->payload()['success']);
    }

    public function testProtectedRouteReturns401WithoutToken(): void
    {
        $response = $this->dispatch($this->app, 'GET', '/api/auth/me');
        $this->assertEquals(401, $response->statusCode());
    }

    public function testLoginValidationReturns422(): void
    {
        $response = $this->dispatch($this->app, 'POST', '/api/auth/login', []);
        $this->assertEquals(422, $response->statusCode());
    }

    public function testCacheSetGetForget(): void
    {
        Cache::set('test_key', 'test_value', 60);
        $this->assertSame('test_value', Cache::get('test_key'));
        Cache::forget('test_key');
        $this->assertNull(Cache::get('test_key'));
    }

    public function testCacheFlush(): void
    {
        Cache::set('test_flush', 'val', 60);
        Cache::flush();
        $this->assertNull(Cache::get('test_flush'));
    }

    public function testStoragePutGetDelete(): void
    {
        $path = 'test_' . time() . '.txt';
        Storage::put($path, 'hello');
        $this->assertSame('hello', Storage::get($path));
        Storage::delete($path);
        $this->assertFalse(Storage::exists($path));
    }

    public function testStorageExists(): void
    {
        $path = 'test_exists_' . time() . '.txt';
        $this->assertFalse(Storage::exists($path));
        Storage::put($path, 'x');
        $this->assertTrue(Storage::exists($path));
        Storage::delete($path);
    }

    public function testValidatorCustomRuleViaExtend(): void
    {
        Validator::extend('positive', fn ($value) => $value > 0);
        $errors = Validator::make(['n' => -1], ['n' => 'positive']);
        $this->assertArrayHasKey('n', $errors);
    }

    public function testResponseSuccessStructure(): void
    {
        $r = Response::success(['id' => 1], 'OK');
        $p = $r->payload();
        $this->assertTrue($p['success']);
        $this->assertSame('OK', $p['message']);
        $this->assertArrayHasKey('data', $p);
        $this->assertArrayHasKey('meta', $p);
    }

    public function testResponseErrorStructure(): void
    {
        $r = Response::error('Not found', 404);
        $p = $r->payload();
        $this->assertFalse($p['success']);
        $this->assertSame('Not found', $p['message']);
        $this->assertNull($p['data']);
    }

    public function testResponsePaginatedStructure(): void
    {
        $r = Response::paginated([], ['page' => 1, 'per_page' => 15, 'total' => 0, 'last_page' => 0], 'OK');
        $p = $r->payload();
        $this->assertTrue($p['success']);
        $this->assertArrayHasKey('data', $p);
        $this->assertArrayHasKey('page', $p['meta']);
    }

    public function testXssInRequestBodyPassesLengthValidation(): void
    {
        $errors = Validator::make(['name' => '<script>alert(1)</script>'], ['name' => 'required|max:100']);
        $this->assertSame([], $errors);
    }

    public function testSqlInjectionAttemptFailsEmailValidation(): void
    {
        $errors = Validator::make(['email' => "'; DROP TABLE users;--"], ['email' => 'required|email']);
        $this->assertArrayHasKey('email', $errors);
    }

    public function testValidateRequiredOnEmptyArray(): void
    {
        $errors = Validator::make([], ['name' => 'required']);
        $this->assertArrayHasKey('name', $errors);
    }

    public function testValidateNumericFieldWithString(): void
    {
        $errors = Validator::make(['price' => 'not-a-number'], ['price' => 'numeric']);
        $this->assertArrayHasKey('price', $errors);
    }

    public function testValidateNumericFieldWithNumber(): void
    {
        $errors = Validator::make(['price' => '99.99'], ['price' => 'numeric']);
        $this->assertSame([], $errors);
    }

    public function testValidateEmailWithInvalidFormat(): void
    {
        $errors = Validator::make(['email' => 'not-an-email'], ['email' => 'email']);
        $this->assertArrayHasKey('email', $errors);
    }

    public function testValidateIntegerFieldWithString(): void
    {
        $errors = Validator::make(['count' => 'abc'], ['count' => 'integer']);
        $this->assertArrayHasKey('count', $errors);
    }

    public function testValidateIntegerFieldWithNumber(): void
    {
        $errors = Validator::make(['count' => '42'], ['count' => 'integer']);
        $this->assertSame([], $errors);
    }

    public function testValidateMinLengthString(): void
    {
        $errors = Validator::make(['name' => 'ab'], ['name' => 'min:3']);
        $this->assertArrayHasKey('name', $errors);
    }

    public function testValidateMaxLengthString(): void
    {
        $errors = Validator::make(['name' => str_repeat('a', 101)], ['name' => 'max:100']);
        $this->assertArrayHasKey('name', $errors);
    }

    public function testValidateInRuleWithValidValue(): void
    {
        $errors = Validator::make(['status' => 'active'], ['status' => 'in:active,inactive']);
        $this->assertSame([], $errors);
    }

    public function testValidateInRuleWithInvalidValue(): void
    {
        $errors = Validator::make(['status' => 'banned'], ['status' => 'in:active,inactive']);
        $this->assertArrayHasKey('status', $errors);
    }

    public function testValidateMultipleFieldsSimultaneously(): void
    {
        $errors = Validator::make(
            ['name' => '', 'email' => 'bad', 'age' => '-1'],
            ['name' => 'required', 'email' => 'email', 'age' => 'min:0']
        );
        $this->assertCount(3, $errors);
    }

    public function testValidateConfirmedField(): void
    {
        $errors = Validator::make(
            ['password' => 'secret', 'password_confirmation' => 'different'],
            ['password' => 'confirmed']
        );
        $this->assertArrayHasKey('password', $errors);
    }

    public function testValidateConfirmedFieldPasses(): void
    {
        $errors = Validator::make(
            ['password' => 'secret', 'password_confirmation' => 'secret'],
            ['password' => 'confirmed']
        );
        $this->assertSame([], $errors);
    }

    public function testValidateUrlFieldWithInvalidUrl(): void
    {
        $errors = Validator::make(['website' => 'not-a-url'], ['website' => 'url']);
        $this->assertArrayHasKey('website', $errors);
    }

    public function testValidatorExtendAddsCustomRule(): void
    {
        Validator::extend('uppercase', function ($value) {
            return strtoupper($value) === $value ? true : ':field must be uppercase';
        });
        $errors = Validator::make(['code' => 'abc'], ['code' => 'uppercase']);
        $this->assertArrayHasKey('code', $errors);
    }

    public function testValidatorExtendPassesForValidInput(): void
    {
        Validator::extend('uppercase2', function ($value) {
            return strtoupper($value) === $value ? true : ':field must be uppercase';
        });
        $errors = Validator::make(['code' => 'ABC'], ['code' => 'uppercase2']);
        $this->assertSame([], $errors);
    }

    public function testResponseCreatedReturnsCorrectPayload(): void
    {
        $r = Response::created(['id' => 1], 'Created');
        $this->assertEquals(201, $r->statusCode());
    }

    public function testResponseNoContentReturns204(): void
    {
        $r = Response::noContent();
        $this->assertEquals(204, $r->statusCode());
    }
}
