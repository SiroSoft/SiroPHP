<?php

declare(strict_types=1);

namespace App\Tests\Feature;

use App\Tests\TestCase;

final class fromtracedemo_9f196c1e66e4fdbfd6ffa17f8092Test extends TestCase
{
    public function testIndexReturns200(): void
    {
        $this->get('/api/fromtracedemo_9f196c1e66e4fdbfd6ffa17f8092')->assertOk();
    }

    public function testShowReturns404ForInvalidId(): void
    {
        $this->get('/api/fromtracedemo_9f196c1e66e4fdbfd6ffa17f8092/999')->assertNotFound();
    }

    public function testStoreReturns201WithValidData(): void
    {
        $this->post('/api/fromtracedemo_9f196c1e66e4fdbfd6ffa17f8092', ['name' => 'Test'])->assertCreated();
    }

    public function testStoreReturns422WithoutRequiredFields(): void
    {
        $this->post('/api/fromtracedemo_9f196c1e66e4fdbfd6ffa17f8092', [])->assertValidationError();
    }
}
