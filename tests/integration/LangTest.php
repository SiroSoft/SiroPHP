<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Tests\TestCase;
use Siro\Core\Lang;
use Siro\Core\Validator;

final class LangTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Lang::setLocale('en');
    }

    public function testGetReturnsEnglishByDefault(): void
    {
        $this->assertSame('Welcome', Lang::get('messages.welcome'));
    }

    public function testGetReturnsVietnameseWhenSet(): void
    {
        Lang::setLocale('vi');
        $this->assertSame('Chào mừng', Lang::get('messages.welcome'));
    }

    public function testGetReturnsKeyWhenNotFound(): void
    {
        $this->assertSame('nonexistent.key', Lang::get('nonexistent.key'));
    }

    public function testGetWithParameterReplacement(): void
    {
        $this->assertSame('Name is required', Lang::get('validation.required', ['field' => 'Name']));
    }

    public function testGetReplacesMultipleParams(): void
    {
        $this->assertSame('Email must be at least 6', Lang::get('validation.min', ['field' => 'Email', 'min' => '6']));
    }

    public function testHasChecksKeyExistence(): void
    {
        $this->assertTrue(Lang::has('messages.welcome'));
        $this->assertFalse(Lang::has('messages.nonexistent'));
    }

    public function testLocaleReturnsCurrentLocale(): void
    {
        Lang::setLocale('fr');
        $this->assertSame('fr', Lang::locale());
    }

    public function testValidatorUsesLangEnMessages(): void
    {
        Lang::setLocale('en');
        $errors = Validator::make(['email' => ''], ['email' => 'required']);
        $this->assertSame(['Email is required'], $errors['email'] ?? []);
    }

    public function testValidatorUsesLangViMessages(): void
    {
        Lang::setLocale('vi');
        $errors = Validator::make(['email' => ''], ['email' => 'required']);
        $this->assertSame(['Email không được để trống'], $errors['email'] ?? []);
    }

    public function testValidatorMinViMessage(): void
    {
        Lang::setLocale('vi');
        $errors = Validator::make(['name' => 'ab'], ['name' => 'min:3']);
        $this->assertSame(['Name phải có ít nhất 3 ký tự'], $errors['name'] ?? []);
    }

    public function testValidatorEmailViMessage(): void
    {
        Lang::setLocale('vi');
        $errors = Validator::make(['email' => 'not-email'], ['email' => 'email']);
        $this->assertSame(['Email không đúng định dạng email'], $errors['email'] ?? []);
    }

    public function testVietnameseParams(): void
    {
        Lang::setLocale('vi');
        $this->assertSame('Email không được để trống', Lang::get('validation.required', ['field' => 'Email']));
        $this->assertSame('Email không đúng định dạng email', Lang::get('validation.email', ['field' => 'Email']));
        $this->assertSame('Mật khẩu phải có ít nhất 8 ký tự', Lang::get('validation.min', ['field' => 'Mật khẩu', 'min' => '8']));
        $this->assertSame('Mật khẩu không được vượt quá 100 ký tự', Lang::get('validation.max', ['field' => 'Mật khẩu', 'max' => '100']));
    }
}
