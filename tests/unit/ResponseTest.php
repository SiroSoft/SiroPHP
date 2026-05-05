<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Tests\TestCase;
use Siro\Core\Response;

final class ResponseTest extends TestCase
{
    public function testSuccessReturnsCorrectStructure(): void
    {
        $r = Response::success(['id' => 1], 'OK');
        $p = $r->payload();
        $this->assertTrue($p['success']);
        $this->assertSame(['id' => 1], $p['data']);
        $this->assertSame('OK', $p['message']);
    }

    public function testSuccessReturns200(): void { $this->assertEquals(200, Response::success()->statusCode()); }
    public function testErrorReturnsCorrectStructure(): void
    {
        $r = Response::error('Not found', 404);
        $p = $r->payload();
        $this->assertFalse($p['success']);
        $this->assertSame('Not found', $p['message']);
    }

    public function testErrorReturnsGivenStatus(): void { $this->assertEquals(422, Response::error('bad', 422)->statusCode()); }
    public function testCreatedReturns201(): void { $this->assertEquals(201, Response::created(['id' => 1])->statusCode()); }
    public function testNoContentReturns204(): void { $this->assertEquals(204, Response::noContent()->statusCode()); }

    public function testPaginatedReturnsCorrectStructure(): void
    {
        $r = Response::paginated([1, 2, 3], ['page' => 1, 'total' => 3]);
        $p = $r->payload();
        $this->assertTrue($p['success']);
        $this->assertSame([1, 2, 3], $p['data']);
        $this->assertSame(3, $p['meta']['total']);
    }

    public function testJsonPayloadIsPreserved(): void
    {
        $r = Response::json(['custom' => 'data'], 201);
        $this->assertSame(['custom' => 'data'], $r->payload());
        $this->assertEquals(201, $r->statusCode());
    }
}
