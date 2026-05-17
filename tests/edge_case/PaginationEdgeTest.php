<?php

declare(strict_types=1);

namespace App\Tests\EdgeCase;

use App\Tests\TestCase;

final class PaginationEdgeTest extends TestCase
{
    public function testNegativePageNumber(): void
    {
        $auth = $this->authenticate();
        $resp = $this->get('/api/products?page=-1', $auth);
        $resp->assertOk();
    }

    public function testZeroPerPage(): void
    {
        $auth = $this->authenticate();
        $resp = $this->get('/api/products?per_page=0', $auth);
        $resp->assertOk();
    }

    public function testHugePerPage(): void
    {
        $auth = $this->authenticate();
        $resp = $this->get('/api/products?per_page=9999', $auth);
        $resp->assertOk();
    }

    public function testStringPage(): void
    {
        $auth = $this->authenticate();
        $resp = $this->get('/api/products?page=abc', $auth);
        $resp->assertOk();
    }
}
