<?php

declare(strict_types=1);

namespace App\Tests\Feature;

use App\Tests\TestCase;
use Siro\Core\Resource;

final class ResourcePatternTest extends TestCase
{
    public function testResourceClassExists(): void { $this->assertTrue(class_exists(Resource::class)); }
    public function testResourceHasMakeMethod(): void { $this->assertTrue(method_exists(Resource::class, 'make')); }
    public function testResourceHasCollectionMethod(): void { $this->assertTrue(method_exists(Resource::class, 'collection')); }

    public function testResourceMakeWithConcreteClassReturnsArray(): void
    {
        $r = TestResource::make(['id' => 1, 'name' => 'Test']);
        $this->assertIsArray($r);
        $this->assertArrayHasKey('id', $r);
    }

    public function testResourceCollectionWithConcreteClassReturnsArray(): void
    {
        $c = TestResource::collection([['id' => 1], ['id' => 2]]);
        $this->assertIsArray($c);
        $this->assertCount(2, $c);
    }
}

final class TestResource extends Resource
{
    public function toArray(): array { return $this->data; }
}
