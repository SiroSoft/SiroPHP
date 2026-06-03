<?php

declare(strict_types=1);

namespace App\Tests\Feature;

use App\Tests\TestCase;

final class ProductTest extends TestCase
{
    private static array $adminHeaders = [];
    private static bool $initialized = false;

    protected function setUp(): void
    {
        parent::setUp();
        if (!self::$initialized) {
            self::$initialized = true;
            $dbFile = dirname(__DIR__, 2) . '/storage/tests/' . str_replace('\\', '_', static::class) . '.db';
            if (file_exists($dbFile)) {
                @unlink($dbFile);
            }
            $app = $this->createApp();
            self::$adminHeaders = $this->authenticate($app);
        }
    }

    public function testIndexReturns200(): void
    {
        $this->get('/api/products', self::$adminHeaders)->assertOk();
    }

    public function testShowReturns404ForInvalidId(): void
    {
        $this->get('/api/products/999', self::$adminHeaders)->assertNotFound();
    }

    public function testStoreReturns201WithValidData(): void
    {
        $this->post('/api/products', ['name' => 'Test Product', 'price' => 10, 'stock' => 5], self::$adminHeaders)->assertCreated();
    }

    public function testStoreReturns422WithoutRequiredFields(): void
    {
        $this->post('/api/products', [], self::$adminHeaders)->assertValidationError();
    }
}
