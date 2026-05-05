<?php

declare(strict_types=1);

namespace App\Tests\Feature;

use App\Tests\TestCase;
use Siro\Core\Response;

final class FileDownloadTest extends TestCase
{
    private string $tempFile;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tempFile = tempnam(sys_get_temp_dir(), 'siro_test_');
        file_put_contents($this->tempFile, 'test content');
    }

    protected function tearDown(): void
    {
        if (file_exists($this->tempFile)) unlink($this->tempFile);
        parent::tearDown();
    }

    public function testResponseHasDownloadMethod(): void { $this->assertTrue(method_exists(Response::class, 'download')); }
    public function testResponseHasFileMethod(): void { $this->assertTrue(method_exists(Response::class, 'file')); }
    public function testFileExists(): void { $this->assertFileExists($this->tempFile); $this->assertSame('test content', file_get_contents($this->tempFile)); }
}
