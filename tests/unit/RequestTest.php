<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Tests\TestCase;
use Siro\Core\Request;
use Siro\Core\ValidationException;

final class RequestTest extends TestCase
{
    private Request $request;

    protected function setUp(): void
    {
        parent::setUp();
        $this->request = new Request('POST', '/test', ['page' => '2', 'search' => 'hello'], ['content-type' => 'application/json'], ['name' => 'John', 'age' => '25', 'active' => 'true', 'price' => '19.99', 'tags' => ['a', 'b']], '127.0.0.1');
    }

    public function testMethodReturnsCorrect(): void { $this->assertSame('POST', $this->request->method()); }
    public function testPathReturnsCorrect(): void { $this->assertSame('/test', $this->request->path()); }
    public function testInputReturnsFromBody(): void { $this->assertSame('John', $this->request->input('name')); }
    public function testInputReturnsDefaultForMissing(): void { $this->assertSame('default', $this->request->input('missing', 'default')); }
    public function testQueryReturnsFromQueryParams(): void { $this->assertSame('2', $this->request->query('page')); }
    public function testIntReturnsInteger(): void { $this->assertSame(25, $this->request->int('age')); }
    public function testIntReturnsDefaultForMissing(): void { $this->assertSame(99, $this->request->int('missing', 99)); }
    public function testStringReturnsString(): void { $this->assertSame('John', $this->request->string('name')); }
    public function testFloatReturnsFloat(): void { $this->assertSame(19.99, $this->request->float('price')); }
    public function testBoolReturnsTrue(): void { $this->assertTrue($this->request->bool('active')); }
    public function testBoolReturnsFalseForMissing(): void { $this->assertFalse($this->request->bool('missing')); }
    public function testArrayReturnsArray(): void { $this->assertSame(['a', 'b'], $this->request->array('tags')); }
    public function testValidatePasses(): void { $this->assertSame(['name' => 'John'], $this->request->validate(['name' => 'required'])); }
    public function testValidateThrowsOnFailure(): void { $this->expectException(ValidationException::class); $this->request->validate(['missing' => 'required']); }
    public function testOnlyReturnsSpecifiedKeys(): void { $this->assertSame(['name' => 'John', 'age' => '25'], $this->request->only(['name', 'age'])); }
    public function testExceptRemovesSpecifiedKeys(): void { $data = $this->request->except(['name']); $this->assertArrayNotHasKey('name', $data); $this->assertArrayHasKey('age', $data); }
}
