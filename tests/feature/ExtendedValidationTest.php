<?php

declare(strict_types=1);

namespace App\Tests\Feature;

use App\Tests\TestCase;
use Siro\Core\Validator;

final class ExtendedValidationTest extends TestCase
{
    public function testDatePassesValid(): void { $this->assertSame([], Validator::make(['date' => '2024-01-01'], ['date' => 'date'])); }
    public function testDateFailsInvalid(): void { $this->assertArrayHasKey('date', Validator::make(['date' => 'not-a-date'], ['date' => 'date'])); }
    public function testUrlPassesValid(): void { $this->assertSame([], Validator::make(['url' => 'https://example.com'], ['url' => 'url'])); }
    public function testUrlFailsInvalid(): void { $this->assertArrayHasKey('url', Validator::make(['url' => 'not-a-url'], ['url' => 'url'])); }
    public function testRequiredIfPassesWhenConditionMet(): void { $this->assertSame([], Validator::make(['has_email' => 'yes', 'email' => 'test@test.com'], ['email' => 'required_if:has_email,yes'])); }
    public function testRequiredIfPassesWhenConditionNotMet(): void { $this->assertSame([], Validator::make(['has_email' => 'no'], ['email' => 'required_if:has_email,yes'])); }
    public function testRequiredIfFailsWhenConditionMetAndMissing(): void { $this->assertArrayHasKey('email', Validator::make(['has_email' => 'yes'], ['email' => 'required_if:has_email,yes'])); }
    public function testRegexPassesMatch(): void { $this->assertSame([], Validator::make(['phone' => '0123456789'], ['phone' => 'regex:/^[0-9]{10}$/'])); }
    public function testRegexFailsNoMatch(): void { $this->assertArrayHasKey('phone', Validator::make(['phone' => 'abc'], ['phone' => 'regex:/^[0-9]{10}$/'])); }
}
