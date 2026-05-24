<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Tests\TestCase;
use Siro\Core\Request;

final class OrderServiceTestTest extends TestCase
{
    public function testBasicAssertion(): void
    {
        $request = new Request('GET', '/test');
        $this->assertSame('GET', $request->method());
        $this->assertSame('/test', $request->path());
    }
}
