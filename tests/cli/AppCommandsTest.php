<?php

declare(strict_types=1);

namespace App\Tests\Cli;

use PHPUnit\Framework\TestCase;

class AppCommandsTest extends TestCase
{
    private string $basePath;
    private \Siro\Core\Console $console;

    protected function setUp(): void
    {
        $this->basePath = dirname(__DIR__, 2);
        
        // Create temp dir for test output
        $tempDir = sys_get_temp_dir() . '/siro_app_test_' . bin2hex(random_bytes(4));
        mkdir($tempDir, 0777, true);
        putenv('SIRO_BASE_PATH=' . $this->basePath);
        
        $this->console = new \Siro\Core\Console('');
    }

    public function testSiroScriptExists(): void
    {
        $siroPath = $this->basePath . '/siro';
        $this->assertFileExists($siroPath, 'siro CLI script should exist');
        
        $content = file_get_contents($siroPath);
        $this->assertStringStartsWith('#!', $content, 'siro script should have shebang');
    }

    public function testSiroVersionViaCli(): void
    {
        $output = shell_exec('php ' . escapeshellarg($this->basePath . '/siro') . ' --version 2>&1');
        $this->assertStringContainsString('0.24.0', $output, 'CLI should report v0.24.0');
    }

    public function testRateStatus(): void
    {
        ob_start();
        $exitCode = $this->console->run(['siro', 'rate:status']);
        $output = ob_get_clean();
        $this->assertEquals(0, $exitCode, 'rate:status should exit 0');
    }

    public function testRouteRules(): void
    {
        ob_start();
        $exitCode = $this->console->run(['siro', 'route:rules']);
        $output = ob_get_clean();
        $this->assertEquals(0, $exitCode, 'route:rules should exit 0');
    }

    public function testServeCommandStructure(): void
    {
        // Just test that serve command exists and can be initiated
        ob_start();
        $exitCode = $this->console->run(['siro', 'serve', '--help']);
        $output = ob_get_clean();
        $this->assertEquals(0, $exitCode, 'serve --help should exit 0');
        $this->assertStringContainsString('port', strtolower($output), 'Serve help should mention port');
    }

    public function testApiTestHelp(): void
    {
        ob_start();
        $exitCode = $this->console->run(['siro', 'api:test', '--help']);
        $output = ob_get_clean();
        $this->assertEquals(0, $exitCode, 'api:test --help should exit 0');
    }

    public function testMakeControllerNoArgs(): void
    {
        ob_start();
        $exitCode = $this->console->run(['siro', 'make:controller']);
        $output = ob_get_clean();
        $this->assertEquals(1, $exitCode, 'make:controller without args should exit 1');
        $this->assertStringContainsString('usage', strtolower($output), 'Should show usage info');
    }

    public function testHealthCheckRouteExists(): void
    {
        // Check the routes file references health endpoint
        $routesFile = $this->basePath . '/routes/api.php';
        $this->assertFileExists($routesFile);
        $content = file_get_contents($routesFile);
        $this->assertStringContainsString('/health/ready', $content, 'Routes should include health endpoint');
    }
}
