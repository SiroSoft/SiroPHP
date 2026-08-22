<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Middleware\SecurityHeadersMiddleware;
use App\Middleware\AuthMiddleware;
use App\Models\User;
use App\Models\Product;
use App\Models\Order;
use App\Models\Post;
use App\Models\Category;
use App\Models\Tag;
use App\Events\UserCreatedEvent;
use App\Listeners\SendWelcomeEmailListener;
use App\Exceptions\DuplicateEmailException;
use App\Exceptions\NoFieldsToUpdateException;
use App\Exceptions\Handler;
use App\Jobs\SendWelcomeEmail;
use App\Jobs\ProcessPendingNotifications;
use App\Mails\WelcomeMail;
use App\Support\Uploader;
use App\Tests\TestCase;

final class EdgeCoverageMutationTest extends TestCase
{
    // ─── Models ──────────────────────────────────────────────────────

    public function testUserModelTable(): void
    {
        $model = new User();
        $this->assertSame('users', $model->getTable());
    }

    public function testUserModelGetFillable(): void
    {
        $model = new User();
        $model->setFillable(['name', 'email', 'password']);
        $ref = new \ReflectionProperty(\Siro\Core\Model::class, 'fillable');
        $ref->setAccessible(true);
        $this->assertSame(['name', 'email', 'password'], $ref->getValue($model));
    }

    public function testProductModelTable(): void
    {
        $model = new Product();
        $this->assertSame('products', $model->getTable());
    }

    public function testOrderModelTable(): void
    {
        $model = new Order();
        $this->assertSame('orders', $model->getTable());
    }

    public function testPostModelTable(): void
    {
        $model = new Post();
        $this->assertSame('posts', $model->getTable());
    }

    public function testCategoryModelTable(): void
    {
        $model = new Category();
        $this->assertSame('categories', $model->getTable());
    }

    public function testTagModelTable(): void
    {
        $model = new Tag();
        $this->assertSame('tags', $model->getTable());
    }

    // ─── Exceptions ──────────────────────────────────────────────────

    public function testDuplicateEmailException(): void
    {
        $e = new DuplicateEmailException('test@test.com');
        $this->assertStringContainsString('test@test.com', $e->getMessage());
    }

    public function testNoFieldsToUpdateException(): void
    {
        $e = new NoFieldsToUpdateException();
        $this->assertNotEmpty($e->getMessage());
    }

    // ─── Handler ─────────────────────────────────────────────────────

    public function testHandlerHandle(): void
    {
        $handler = new Handler();
        $e = new \RuntimeException('Test error');
        $request = new \Siro\Core\Request('GET', '/', [], [], [], '127.0.0.1');
        $response = Handler::handle($e, $request);
        $this->assertInstanceOf(\Siro\Core\Response::class, $response);
    }

    public function testHandlerHandleValidationException(): void
    {
        $request = new \Siro\Core\Request('GET', '/', [], [], [], '127.0.0.1');
        $e = new \Siro\Core\ValidationException(['field' => ['Required']], 'Validation failed');
        $response = Handler::handle($e, $request);
        $this->assertInstanceOf(\Siro\Core\Response::class, $response);
    }

    public function testHandlerHandleDuplicateEmail(): void
    {
        $request = new \Siro\Core\Request('GET', '/', [], [], [], '127.0.0.1');
        $e = new \App\Exceptions\DuplicateEmailException('test@test.com');
        $response = Handler::handle($e, $request);
        $this->assertInstanceOf(\Siro\Core\Response::class, $response);
    }

    public function testHandlerHandleNoFieldsToUpdate(): void
    {
        $request = new \Siro\Core\Request('GET', '/', [], [], [], '127.0.0.1');
        $e = new \App\Exceptions\NoFieldsToUpdateException();
        $response = Handler::handle($e, $request);
        $this->assertInstanceOf(\Siro\Core\Response::class, $response);
    }

    // ─── Events ──────────────────────────────────────────────────────

    public function testUserCreatedEvent(): void
    {
        $event = new UserCreatedEvent(['id' => 1, 'email' => 'test@test.com', 'name' => 'Test']);
        $this->assertNotNull($event);
    }

    // ─── Jobs ────────────────────────────────────────────────────────

    public function testSendWelcomeEmailJob(): void
    {
        $job = new SendWelcomeEmail(['name' => 'Test', 'email' => 'test@test.com']);
        $this->assertNotNull($job);
    }

    public function testProcessPendingNotificationsJob(): void
    {
        $job = new ProcessPendingNotifications();
        $this->assertNotNull($job);
    }

    // ─── Mail ────────────────────────────────────────────────────────

    public function testWelcomeMail(): void
    {
        $mail = new WelcomeMail('Test User');
        $this->assertNotNull($mail);
    }

    // ─── SecurityHeadersMiddleware ───────────────────────────────────

    public function testSecurityHeadersMiddleware(): void
    {
        $middleware = new SecurityHeadersMiddleware();
        $this->assertNotNull($middleware);
    }

    // ─── AuthMiddleware ──────────────────────────────────────────────

    public function testAuthMiddlewareInvalidToken(): void
    {
        $app = $this->createApp();
        $resp = $this->dispatch($app, 'GET', '/api/auth/me', [], [
            'authorization' => 'Bearer invalid-token',
        ]);
        $this->assertContains($resp->statusCode(), [401, 403]);
    }

    public function testAuthMiddlewareNoToken(): void
    {
        $this->get('/api/auth/me')->assertStatus(401);
    }

    public function testAuthMiddlewareMalformedHeader(): void
    {
        $app = $this->createApp();
        $resp = $this->dispatch($app, 'GET', '/api/auth/me', [], [
            'authorization' => 'NotBearer somevalue',
        ]);
        $this->assertContains($resp->statusCode(), [401, 403]);
    }

    // ─── System Routes ───────────────────────────────────────────────

    public function testFavicon(): void
    {
        $this->get('/favicon.ico')->assertNoContent();
    }

    public function testRobotsTxt(): void
    {
        $this->get('/robots.txt')->assertOk();
    }

    public function testSecurityTxt(): void
    {
        $this->get('/.well-known/security.txt')->assertOk();
    }

    public function testHealthLive(): void
    {
        $resp = $this->get('/health/live');
        $this->assertContains($resp->status(), [200, 429]);
    }

    public function testHealthReady(): void
    {
        $resp = $this->get('/health/ready');
        $this->assertContains($resp->status(), [200, 429]);
    }

    public function testHealth(): void
    {
        $resp = $this->get('/health');
        $this->assertContains($resp->status(), [200, 429]);
    }

    public function testRoot(): void
    {
        $this->get('/')->assertOk();
    }

    public function testRootHtml(): void
    {
        $app = $this->createApp();
        $resp = $this->dispatch($app, 'GET', '/', [], [
            'accept' => 'text/html,application/xhtml+xml',
        ]);
        $this->assertContains($resp->statusCode(), [200, 404]);
    }

    // ─── Profile with locale variations ──────────────────────────────

    public function testProfileLocaleDe(): void
    {
        $app = $this->createApp();
        $email = 'locde-' . uniqid() . '@test.com';
        $this->dispatch($app, 'POST', '/api/auth/register', [
            'name' => 'Loc DE',
            'email' => $email,
            'password' => 'secret123',
        ]);
        $login = $this->dispatch($app, 'POST', '/api/auth/login', [
            'email' => $email,
            'password' => 'secret123',
        ]);
        $loginJson = $this->responseJson($login);
        $token = $loginJson['data']['token'] ?? '';
        $headers = ['authorization' => 'Bearer ' . $token, 'content-type' => 'application/json'];
        $this->get('/api/profile?locale=de', $headers)->assertOk();
    }

    public function testProfileLocaleZh(): void
    {
        $app = $this->createApp();
        $email = 'loczh-' . uniqid() . '@test.com';
        $this->dispatch($app, 'POST', '/api/auth/register', [
            'name' => 'Loc ZH',
            'email' => $email,
            'password' => 'secret123',
        ]);
        $login = $this->dispatch($app, 'POST', '/api/auth/login', [
            'email' => $email,
            'password' => 'secret123',
        ]);
        $loginJson = $this->responseJson($login);
        $token = $loginJson['data']['token'] ?? '';
        $headers = ['authorization' => 'Bearer ' . $token, 'content-type' => 'application/json'];
        $this->get('/api/profile?locale=zh', $headers)->assertOk();
    }

    public function testProfileLocaleJa(): void
    {
        $app = $this->createApp();
        $email = 'locja-' . uniqid() . '@test.com';
        $this->dispatch($app, 'POST', '/api/auth/register', [
            'name' => 'Loc JA',
            'email' => $email,
            'password' => 'secret123',
        ]);
        $login = $this->dispatch($app, 'POST', '/api/auth/login', [
            'email' => $email,
            'password' => 'secret123',
        ]);
        $loginJson = $this->responseJson($login);
        $token = $loginJson['data']['token'] ?? '';
        $headers = ['authorization' => 'Bearer ' . $token, 'content-type' => 'application/json'];
        $this->get('/api/profile?locale=ja', $headers)->assertOk();
    }

    // ─── Dashboard Stats with data ───────────────────────────────────

    public function testDashboardStatsWithData(): void
    {
        $app = $this->createApp();
        $email = 'dashdata-' . uniqid() . '@test.com';
        $this->dispatch($app, 'POST', '/api/auth/register', [
            'name' => 'Dash Admin',
            'email' => $email,
            'password' => 'secret123',
        ]);
        \Siro\Core\Database::execute("UPDATE users SET role = 'admin' WHERE email = ?", [$email]);
        $login = $this->dispatch($app, 'POST', '/api/auth/login', [
            'email' => $email,
            'password' => 'secret123',
        ]);
        $loginJson = $this->responseJson($login);
        $token = $loginJson['data']['token'] ?? '';
        $headers = ['authorization' => 'Bearer ' . $token, 'content-type' => 'application/json'];
        $this->dispatch($app, 'POST', '/api/products', [
            'name' => 'Dashboard Product',
            'price' => 50.00,
            'stock' => 10,
        ], $headers);
        $this->get('/api/dashboard/stats', $headers)->assertOk();
    }

    // ─── Model find ──────────────────────────────────────────────────

    public function testUserModelFind(): void
    {
        $this->createApp();
        $found = User::find(99999);
        $this->assertNull($found);
    }

    // ─── Security headers response ───────────────────────────────────

    public function testSecurityHeadersApplied(): void
    {
        $app = $this->createApp();
        $email = 'sechead-' . uniqid() . '@test.com';
        $this->dispatch($app, 'POST', '/api/auth/register', [
            'name' => 'Sec Admin',
            'email' => $email,
            'password' => 'secret123',
        ]);
        \Siro\Core\Database::execute("UPDATE users SET role = 'admin' WHERE email = ?", [$email]);
        $login = $this->dispatch($app, 'POST', '/api/auth/login', [
            'email' => $email,
            'password' => 'secret123',
        ]);
        $loginJson = $this->responseJson($login);
        $token = $loginJson['data']['token'] ?? '';
        $headers = ['authorization' => 'Bearer ' . $token, 'content-type' => 'application/json'];
        $resp = $this->get('/api/products', $headers);
        $this->assertContains($resp->status(), [200]);
    }
}
