<?php

declare(strict_types=1);

namespace App\Tests\EdgeCase;

use App\Tests\TestCase;

final class InputEdgeTest extends TestCase
{
    public function testEmptyRequestBody(): void
    {
        $resp = $this->post('/api/auth/login', []);
        $resp->assertValidationError();
    }

    public function testExtraFieldsIgnored(): void
    {
        $auth = $this->authenticate();
        $resp = $this->post('/api/products', [
            'name' => 'Edge Product',
            'price' => 10,
            'nonexistent_field' => 'should be ignored',
        ], $auth);
        $this->assertContains($resp->status(), [200, 201]);
    }

    public function testSpecialCharactersInName(): void
    {
        $auth = $this->authenticate();
        $resp = $this->post('/api/products', [
            'name' => '<script>alert("xss")</script>',
            'price' => 10,
        ], $auth);
        $this->assertContains($resp->status(), [200, 201]);
    }

    public function testHtmlInUserAgent(): void
    {
        $auth = $this->authenticate();
        $resp = $this->get('/api/products', array_merge($auth, [
            'User-Agent' => '<script>malicious</script>',
        ]));
        $resp->assertOk();
    }

    public function testUnicodeEmail(): void
    {
        $resp = $this->post('/api/auth/register', [
            'name' => 'Unicode',
            'email' => 'user@münchen.de',
            'password' => 'secret123',
        ]);
        $this->assertContains($resp->status(), [200, 201, 422]);
    }
}
