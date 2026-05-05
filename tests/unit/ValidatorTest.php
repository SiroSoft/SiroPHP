<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Tests\TestCase;
use Siro\Core\Validator;

final class ValidatorTest extends TestCase
{
    public function testRequiredPassesWithValue(): void { $this->assertSame([], Validator::make(['name' => 'John'], ['name' => 'required'])); }
    public function testRequiredFailsWithNull(): void { $this->assertArrayHasKey('name', Validator::make(['name' => null], ['name' => 'required'])); }
    public function testRequiredFailsWithEmptyString(): void { $this->assertArrayHasKey('name', Validator::make(['name' => ''], ['name' => 'required'])); }
    public function testEmailPassesValid(): void { $this->assertSame([], Validator::make(['email' => 'a@b.com'], ['email' => 'email'])); }
    public function testEmailFailsInvalid(): void { $this->assertArrayHasKey('email', Validator::make(['email' => 'not-email'], ['email' => 'email'])); }
    public function testNumericPasses(): void { $this->assertSame([], Validator::make(['val' => '42'], ['val' => 'numeric'])); }
    public function testNumericFails(): void { $this->assertArrayHasKey('val', Validator::make(['val' => 'abc'], ['val' => 'numeric'])); }
    public function testIntegerPasses(): void { $this->assertSame([], Validator::make(['val' => 42], ['val' => 'integer'])); }
    public function testIntegerFails(): void { $this->assertArrayHasKey('val', Validator::make(['val' => 4.2], ['val' => 'integer'])); }
    public function testMinPassesString(): void { $this->assertSame([], Validator::make(['val' => 'hello'], ['val' => 'min:3'])); }
    public function testMinFailsString(): void { $this->assertArrayHasKey('val', Validator::make(['val' => 'ab'], ['val' => 'min:3'])); }
    public function testMaxPassesString(): void { $this->assertSame([], Validator::make(['val' => 'ab'], ['val' => 'max:3'])); }
    public function testMaxFailsString(): void { $this->assertArrayHasKey('val', Validator::make(['val' => 'abcd'], ['val' => 'max:3'])); }
    public function testConfirmedPasses(): void { $this->assertSame([], Validator::make(['pwd' => 'secret', 'pwd_confirmation' => 'secret'], ['pwd' => 'confirmed'])); }
    public function testConfirmedFailsMismatch(): void { $this->assertArrayHasKey('pwd', Validator::make(['pwd' => 'secret', 'pwd_confirmation' => 'different'], ['pwd' => 'confirmed'])); }
    public function testInPasses(): void { $this->assertSame([], Validator::make(['status' => 'active'], ['status' => 'in:active,inactive'])); }
    public function testInFails(): void { $this->assertArrayHasKey('status', Validator::make(['status' => 'deleted'], ['status' => 'in:active,inactive'])); }
    public function testNonRequiredEmptyFieldPasses(): void { $this->assertSame([], Validator::make(['name' => ''], ['email' => 'email'])); }
    public function testCustomRuleViaExtend(): void { Validator::extend('positive', fn ($v) => $v > 0); $this->assertArrayHasKey('n', Validator::make(['n' => -1], ['n' => 'positive'])); }
}
