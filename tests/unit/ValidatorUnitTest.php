<?php
declare(strict_types=1);
namespace App\Tests\Unit;
use PHPUnit\Framework\TestCase;
use Siro\Core\Validator;

final class ValidatorUnitTest extends TestCase
{
    public function testRequiredPasses(): void
    {
        $errors = Validator::make(['name' => 'John'], ['name' => 'required']);
        $this->assertSame([], $errors);
    }

    public function testRequiredFails(): void
    {
        $errors = Validator::make(['name' => ''], ['name' => 'required']);
        $this->assertNotSame([], $errors);
    }

    public function testEmailValid(): void
    {
        $errors = Validator::make(['email' => 'test@example.com'], ['email' => 'email']);
        $this->assertSame([], $errors);
    }

    public function testEmailInvalid(): void
    {
        $errors = Validator::make(['email' => 'not-an-email'], ['email' => 'email']);
        $this->assertNotSame([], $errors);
    }

    public function testMinLength(): void
    {
        $errors = Validator::make(['pw' => 'ab'], ['pw' => 'min:3']);
        $this->assertNotSame([], $errors);
    }

    public function testMaxLength(): void
    {
        $errors = Validator::make(['pw' => 'abcdef'], ['pw' => 'max:5']);
        $this->assertNotSame([], $errors);
    }

    public function testNumericRule(): void
    {
        $errors = Validator::make(['age' => 'twenty'], ['age' => 'numeric']);
        $this->assertNotSame([], $errors);
    }

    public function testInRule(): void
    {
        $errors = Validator::make(['status' => 'unknown'], ['status' => 'in:active,pending']);
        $this->assertNotSame([], $errors);
    }
}
