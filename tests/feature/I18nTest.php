<?php

declare(strict_types=1);

namespace App\Tests\Feature;

use App\Tests\TestCase;

final class I18nTest extends TestCase
{
    public function testEnglishGreeting(): void
    {
        $res = $this->get('/api/profile?locale=en&name=World');
        $res->assertOk();
        $body = $res->json();
        $this->assertStringContainsString('Hello', $body['message'] ?? '');
    }

    public function testVietnameseGreeting(): void
    {
        $res = $this->get('/api/profile?locale=vi&name=Dev');
        $res->assertOk();
        $body = $res->json();
        $this->assertStringContainsString('Xin chào', $body['message'] ?? '');
    }

    public function testLocaleDetection(): void
    {
        $res = $this->get('/api/profile?locale=en');
        $res->assertOk();
        $body = $res->json();
        $this->assertEquals('en', $body['data']['locale'] ?? '');
    }

    public function testVietnameseLocale(): void
    {
        $res = $this->get('/api/profile?locale=vi');
        $res->assertOk();
        $body = $res->json();
        $this->assertEquals('vi', $body['data']['locale'] ?? '');
    }

    public function testAvailableLocales(): void
    {
        $res = $this->get('/api/profile?locale=en');
        $res->assertOk();
        $body = $res->json();
        $this->assertArrayHasKey('available_locales', $body['data'] ?? []);
        $this->assertContains('en', $body['data']['available_locales'] ?? []);
        $this->assertContains('vi', $body['data']['available_locales'] ?? []);
    }
}
