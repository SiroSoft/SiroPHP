<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Repositories\UserRepository;
use App\Repositories\RefreshTokenRepository;
use App\Repositories\ProductRepository;
use App\Repositories\OrderRepository;
use App\Repositories\PostRepository;
use App\Repositories\TagRepository;
use App\Repositories\CategoryRepository;
use App\Repositories\BaseRepository;
use App\Services\UserService;
use App\Services\RefreshTokenService;
use App\Services\ProductService;
use App\Services\OrderService;
use App\Services\PostService;
use App\Services\CategoryService;
use App\Services\TagService;
use App\Resources\UserResource;
use App\Resources\ProductResource;
use App\Resources\OrderResource;
use App\Resources\PostResource;
use App\Resources\CategoryResource;
use App\Resources\TagResource;
use App\Models\User;
use App\Models\Product;
use App\Role;
use App\Tests\TestCase;

final class RepositoryServiceResourceMutationTest extends TestCase
{
    // ─── UserRepository ──────────────────────────────────────────────

    public function testUserRepositoryFindByEmail(): void
    {
        $repo = new UserRepository();
        $this->ensureTablesCreated();
        $email = 'repo-test-' . uniqid() . '@test.com';
        $repo->create([
            'name' => 'Repo User',
            'email' => $email,
            'password' => password_hash('secret', PASSWORD_BCRYPT),
            'status' => 1,
            'role' => 'user',
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        $found = $repo->findByEmail($email);
        $this->assertNotNull($found);
        $this->assertSame($email, $found['email']);
    }

    public function testUserRepositoryFindByEmailNotFound(): void
    {
        $repo = new UserRepository();
        $this->assertNull($repo->findByEmail('nonexistent-' . uniqid() . '@test.com'));
    }

    public function testUserRepositoryFindBy(): void
    {
        $repo = new UserRepository();
        $this->ensureTablesCreated();
        $email = 'findby-' . uniqid() . '@test.com';
        $repo->create([
            'name' => 'FindBy User',
            'email' => $email,
            'password' => password_hash('secret', PASSWORD_BCRYPT),
            'status' => 1,
            'role' => 'admin',
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        $found = $repo->findBy('email', $email);
        $this->assertNotNull($found);
    }

    public function testUserRepositoryFindByNotFound(): void
    {
        $repo = new UserRepository();
        $this->assertNull($repo->findBy('email', 'nope-' . uniqid() . '@test.com'));
    }

    public function testUserRepositoryUpdateWhere(): void
    {
        $repo = new UserRepository();
        $this->ensureTablesCreated();
        $email = 'updwhere-' . uniqid() . '@test.com';
        $user = $repo->create([
            'name' => 'UpdWhere User',
            'email' => $email,
            'password' => password_hash('secret', PASSWORD_BCRYPT),
            'status' => 1,
            'role' => 'user',
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        $affected = $repo->updateWhere('id', $user->id, ['name' => 'Updated']);
        $this->assertGreaterThanOrEqual(1, $affected);
    }

    public function testUserRepositoryCount(): void
    {
        $repo = new UserRepository();
        $this->ensureTablesCreated();
        $count = $repo->count();
        $this->assertIsInt($count);
        $this->assertGreaterThanOrEqual(0, $count);
    }

    // ─── RefreshTokenRepository ──────────────────────────────────────

    public function testRefreshTokenRepositoryCreate(): void
    {
        $repo = new RefreshTokenRepository();
        $this->ensureTablesCreated();
        $jti = bin2hex(random_bytes(16));
        $repo->create($jti, 1, 3600);
        $this->assertTrue(true);
    }

    public function testRefreshTokenRepositoryFindActiveByJti(): void
    {
        $repo = new RefreshTokenRepository();
        $this->ensureTablesCreated();
        $jti = bin2hex(random_bytes(16));
        $repo->create($jti, 1, 3600);
        $found = $repo->findActiveByJti($jti);
        $this->assertNotNull($found);
    }

    public function testRefreshTokenRepositoryFindActiveByJtiNotFound(): void
    {
        $repo = new RefreshTokenRepository();
        $this->assertNull($repo->findActiveByJti('nonexistent-jti'));
    }

    public function testRefreshTokenRepositoryRevokeByJti(): void
    {
        $repo = new RefreshTokenRepository();
        $this->ensureTablesCreated();
        $jti = bin2hex(random_bytes(16));
        $repo->create($jti, 1, 3600);
        $repo->revokeByJti($jti);
        $found = $repo->findActiveByJti($jti);
        $this->assertNull($found);
    }

    public function testRefreshTokenRepositoryRevokeAllByUserId(): void
    {
        $repo = new RefreshTokenRepository();
        $this->ensureTablesCreated();
        $repo->create(bin2hex(random_bytes(16)), 1, 3600);
        $repo->revokeAllByUserId(1);
        $this->assertTrue(true);
    }

    public function testRefreshTokenRepositoryFindRevokedByJti(): void
    {
        $repo = new RefreshTokenRepository();
        $this->ensureTablesCreated();
        $jti = bin2hex(random_bytes(16));
        $repo->create($jti, 1, 3600);
        $repo->revokeByJti($jti);
        $revoked = $repo->findRevokedByJti($jti);
        $this->assertNotNull($revoked);
    }

    public function testRefreshTokenRepositoryFindRevokedByJtiNotFound(): void
    {
        $repo = new RefreshTokenRepository();
        $this->assertNull($repo->findRevokedByJti('no-such-jti'));
    }

    // ─── BaseRepository ──────────────────────────────────────────────

    public function testBaseRepositoryCreateAndFindById(): void
    {
        $repo = new UserRepository();
        $this->ensureTablesCreated();
        $email = 'base-test-' . uniqid() . '@test.com';
        $user = $repo->create([
            'name' => 'Base Test',
            'email' => $email,
            'password' => password_hash('secret', PASSWORD_BCRYPT),
            'status' => 1,
            'role' => 'user',
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        $found = $repo->findById($user->id);
        $this->assertNotNull($found);
        $this->assertSame($email, $found['email']);
    }

    public function testBaseRepositoryFindByIdNotFound(): void
    {
        $repo = new UserRepository();
        $this->assertNull($repo->findById(99999));
    }

    public function testBaseRepositoryUpdate(): void
    {
        $repo = new UserRepository();
        $this->ensureTablesCreated();
        $email = 'upd-test-' . uniqid() . '@test.com';
        $user = $repo->create([
            'name' => 'Update Test',
            'email' => $email,
            'password' => password_hash('secret', PASSWORD_BCRYPT),
            'status' => 1,
            'role' => 'user',
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        $repo->update($user->id, ['name' => 'Updated Name']);
        $found = $repo->findById($user->id);
        $this->assertSame('Updated Name', $found['name']);
    }

    public function testBaseRepositoryDestroy(): void
    {
        $repo = new UserRepository();
        $this->ensureTablesCreated();
        $email = 'destroy-test-' . uniqid() . '@test.com';
        $user = $repo->create([
            'name' => 'Destroy Test',
            'email' => $email,
            'password' => password_hash('secret', PASSWORD_BCRYPT),
            'status' => 1,
            'role' => 'user',
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        $result = $repo->destroy($user->id);
        $this->assertTrue($result);
        $row = \Siro\Core\Database::first('SELECT id FROM users WHERE id = ?', [$user->id]);
        $this->assertNull($row, 'User row should be removed from database after destroy');
    }

    public function testBaseRepositoryPaginate(): void
    {
        $repo = new UserRepository();
        $this->ensureTablesCreated();
        $result = $repo->findAll([], 1, 10);
        $this->assertIsArray($result);
        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('meta', $result);
    }

    // ─── ProductService ──────────────────────────────────────────────

    public function testProductServiceGetAll(): void
    {
        $service = new ProductService(new ProductRepository());
        $result = $service->getAll();
        $this->assertIsArray($result);
        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('meta', $result);
    }

    public function testProductServiceGetAllWithFilters(): void
    {
        $service = new ProductService(new ProductRepository());
        $result = $service->getAll([
            'sort' => 'price',
            'order' => 'asc',
            'category' => 'test',
            'status' => 'active',
            'price_min' => 10,
            'price_max' => 100,
            'search' => 'test',
        ]);
        $this->assertIsArray($result);
    }

    public function testProductServiceGetAllInvalidSort(): void
    {
        $service = new ProductService(new ProductRepository());
        $result = $service->getAll(['sort' => 'invalid', 'order' => 'invalid']);
        $this->assertIsArray($result);
    }

    public function testProductServiceCreate(): void
    {
        $service = new ProductService(new ProductRepository());
        $this->ensureTablesCreated();
        $product = $service->create([
            'name' => 'Service Product',
            'price' => 29.99,
            'stock' => 10,
            'user_id' => 1,
        ]);
        $this->assertNotNull($product);
        $this->assertSame('Service Product', $product['name']);
    }

    public function testProductServiceUpdate(): void
    {
        $service = new ProductService(new ProductRepository());
        $this->ensureTablesCreated();
        $product = $service->create([
            'name' => 'Update Service Product',
            'price' => 10.00,
            'stock' => 5,
            'user_id' => 1,
        ]);
        $updated = $service->update($product->id, [
            'name' => 'Updated Service Product',
            'price' => 15.00,
            'stock' => 8,
        ]);
        $this->assertNotNull($updated);
        $this->assertSame('Updated Service Product', $updated['name']);
    }

    public function testProductServiceUpdateNotFound(): void
    {
        $service = new ProductService(new ProductRepository());
        $this->assertNull($service->update(99999, ['name' => 'Nope']));
    }

    // ─── OrderService ────────────────────────────────────────────────

    public function testOrderServiceGetAll(): void
    {
        $service = new OrderService(new OrderRepository());
        $result = $service->getAll();
        $this->assertIsArray($result);
        $this->assertArrayHasKey('data', $result);
    }

    public function testOrderServiceGetAllWithFilters(): void
    {
        $service = new OrderService(new OrderRepository());
        $result = $service->getAll(['status' => 'pending', 'user_id' => 1]);
        $this->assertIsArray($result);
    }

    public function testOrderServiceCreate(): void
    {
        $service = new OrderService(new OrderRepository());
        $this->ensureTablesCreated();
        $order = $service->create([
            'customer_name' => 'Test Customer',
            'customer_email' => 'test@test.com',
            'items' => [['product_id' => 1, 'price' => 10.00, 'quantity' => 2]],
            'total' => 20.00,
            'status' => 'pending',
            'user_id' => 1,
        ]);
        $this->assertNotNull($order);
    }

    public function testOrderServiceCreateMaxItems(): void
    {
        $service = new OrderService(new OrderRepository());
        $this->ensureTablesCreated();
        $this->expectException(\InvalidArgumentException::class);
        $items = [];
        for ($i = 0; $i < 51; $i++) {
            $items[] = ['product_id' => 1, 'price' => 10, 'quantity' => 1];
        }
        $service->create([
            'customer_name' => 'Max Items',
            'customer_email' => 'max@test.com',
            'items' => $items,
            'total' => 510.00,
            'status' => 'pending',
            'user_id' => 1,
        ]);
    }

    public function testOrderServiceUpdate(): void
    {
        $service = new OrderService(new OrderRepository());
        $this->ensureTablesCreated();
        $order = $service->create([
            'customer_name' => 'Upd Order',
            'customer_email' => 'upd@test.com',
            'items' => [['product_id' => 1, 'price' => 10, 'quantity' => 1]],
            'total' => 10.00,
            'status' => 'pending',
            'user_id' => 1,
        ]);
        $updated = $service->update($order->id, [
            'customer_name' => 'Updated Order',
        ]);
        $this->assertNotNull($updated);
    }

    public function testOrderServiceUpdateWithItems(): void
    {
        $service = new OrderService(new OrderRepository());
        $this->ensureTablesCreated();
        $order = $service->create([
            'customer_name' => 'Items Order',
            'customer_email' => 'items@test.com',
            'items' => [['product_id' => 1, 'price' => 10, 'quantity' => 1]],
            'total' => 10.00,
            'status' => 'pending',
            'user_id' => 1,
        ]);
        $updated = $service->update($order->id, [
            'items' => [['product_id' => 1, 'price' => 20, 'quantity' => 2]],
        ]);
        $this->assertNotNull($updated);
    }

    // ─── PostService ─────────────────────────────────────────────────

    public function testPostServiceGetAll(): void
    {
        $service = new PostService(new PostRepository());
        $result = $service->getAll();
        $this->assertIsArray($result);
        $this->assertArrayHasKey('data', $result);
    }

    public function testPostServiceGetAllWithFilters(): void
    {
        $service = new PostService(new PostRepository());
        $result = $service->getAll(['locale' => 'en', 'user_id' => 1]);
        $this->assertIsArray($result);
    }

    public function testPostServiceCreate(): void
    {
        $service = new PostService(new PostRepository());
        $this->ensureTablesCreated();
        $post = $service->create([
            'title' => 'Service Post',
            'body' => 'Body with enough content for validation.',
            'locale' => 'en',
            'status' => 'draft',
            'user_id' => 1,
        ]);
        $this->assertNotNull($post);
    }

    public function testPostServiceDeleteNotFound(): void
    {
        $service = new PostService(new PostRepository());
        $this->assertFalse($service->delete(99999));
    }

    public function testPostServiceNotFoundMessage(): void
    {
        $service = new PostService(new PostRepository());
        $msg = $service->notFoundMessage();
        $this->assertIsString($msg);
        $this->assertNotEmpty($msg);
    }

    // ─── AbstractService ─────────────────────────────────────────────

    public function testAbstractServiceGetAll(): void
    {
        $service = new CategoryService(new CategoryRepository());
        $result = $service->getAll();
        $this->assertIsArray($result);
        $this->assertArrayHasKey('data', $result);
    }

    public function testAbstractServiceGetById(): void
    {
        $service = new TagService(new TagRepository());
        $this->assertNull($service->getById(99999));
    }

    // ─── UserService ─────────────────────────────────────────────────

    public function testUserServiceGetByEmail(): void
    {
        $service = new UserService(new UserRepository(), new RefreshTokenRepository());
        $this->assertNull($service->getByEmail('nonexistent-' . uniqid() . '@test.com'));
    }

    public function testUserServiceGetTokenVersion(): void
    {
        $service = new UserService(new UserRepository(), new RefreshTokenRepository());
        $version = $service->getTokenVersion(99999);
        $this->assertSame(1, $version);
    }

    public function testUserServiceIncrementTokenVersionInvalid(): void
    {
        $service = new UserService(new UserRepository(), new RefreshTokenRepository());
        $this->assertFalse($service->incrementTokenVersion(0));
        $this->assertFalse($service->incrementTokenVersion(99999));
    }

    public function testUserServiceIsLocked(): void
    {
        $service = new UserService(new UserRepository(), new RefreshTokenRepository());
        $this->assertFalse($service->isLocked(99999));
    }

    public function testUserServiceCreateDuplicateEmail(): void
    {
        $this->expectException(\App\Exceptions\DuplicateEmailException::class);
        $service = new UserService(new UserRepository(), new RefreshTokenRepository());
        $this->ensureTablesCreated();
        $email = 'dup-svc-' . uniqid() . '@test.com';
        $service->create([
            'name' => 'First',
            'email' => $email,
            'password' => 'secret123',
        ]);
        $service->create([
            'name' => 'Second',
            'email' => $email,
            'password' => 'secret123',
        ]);
    }

    public function testUserServiceUpdateNotFound(): void
    {
        $service = new UserService(new UserRepository(), new RefreshTokenRepository());
        $this->assertNull($service->update(99999, ['name' => 'Test']));
    }

    public function testUserServiceUpdateEmptyData(): void
    {
        $this->expectException(\App\Exceptions\NoFieldsToUpdateException::class);
        $service = new UserService(new UserRepository(), new RefreshTokenRepository());
        $this->ensureTablesCreated();
        $user = (new UserRepository())->create([
            'name' => 'Empty Update',
            'email' => 'empty-' . uniqid() . '@test.com',
            'password' => password_hash('secret', PASSWORD_BCRYPT),
            'status' => 1,
            'role' => 'user',
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        $service->update($user->id, []);
    }

    public function testUserServiceChangePasswordUserNotFound(): void
    {
        $service = new UserService(new UserRepository(), new RefreshTokenRepository());
        $result = $service->changePassword(99999, 'old', 'new');
        $this->assertFalse($result['success']);
        $this->assertSame(404, $result['code']);
    }

    public function testUserServiceChangePasswordWrongCurrent(): void
    {
        $service = new UserService(new UserRepository(), new RefreshTokenRepository());
        $this->ensureTablesCreated();
        $user = (new UserRepository())->create([
            'name' => 'Chg PW',
            'email' => 'chg-' . uniqid() . '@test.com',
            'password' => password_hash('secret123', PASSWORD_BCRYPT),
            'status' => 1,
            'role' => 'user',
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        $result = $service->changePassword($user->id, 'wrongpassword', 'newsecret');
        $this->assertFalse($result['success']);
        $this->assertSame(400, $result['code']);
    }

    public function testUserServiceChangePasswordSuccess(): void
    {
        $service = new UserService(new UserRepository(), new RefreshTokenRepository());
        $this->ensureTablesCreated();
        $user = (new UserRepository())->create([
            'name' => 'Chg PW OK',
            'email' => 'chgok-' . uniqid() . '@test.com',
            'password' => password_hash('secret123', PASSWORD_BCRYPT),
            'status' => 1,
            'role' => 'user',
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        $result = $service->changePassword($user->id, 'secret123', 'newsecret456');
        $this->assertTrue($result['success']);
    }

    // ─── RefreshTokenService ─────────────────────────────────────────

    public function testRefreshTokenServiceCreatePair(): void
    {
        $userService = new UserService(new UserRepository(), new RefreshTokenRepository());
        $service = new RefreshTokenService(new RefreshTokenRepository(), $userService);
        $this->ensureTablesCreated();
        $user = (new UserRepository())->create([
            'name' => 'Token Pair',
            'email' => 'pair-' . uniqid() . '@test.com',
            'password' => password_hash('secret', PASSWORD_BCRYPT),
            'status' => 1,
            'role' => 'user',
            'token_version' => 1,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        $tokens = $service->createPair($user->id);
        $this->assertArrayHasKey('token', $tokens);
        $this->assertArrayHasKey('refresh_token', $tokens);
        $this->assertArrayHasKey('ttl', $tokens);
        $this->assertNotEmpty($tokens['token']);
        $this->assertNotEmpty($tokens['refresh_token']);
    }

    public function testRefreshTokenServiceVerifyAndRotateInvalidToken(): void
    {
        $userService = new UserService(new UserRepository(), new RefreshTokenRepository());
        $service = new RefreshTokenService(new RefreshTokenRepository(), $userService);
        $this->assertNull($service->verifyAndRotate('invalid-token'));
    }

    // ─── Resources ───────────────────────────────────────────────────

    public function testUserResourceMake(): void
    {
        $this->ensureTablesCreated();
        $user = (new UserRepository())->create([
            'name' => 'Resource User',
            'email' => 'res-' . uniqid() . '@test.com',
            'password' => password_hash('secret', PASSWORD_BCRYPT),
            'status' => 1,
            'role' => 'user',
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        $resource = UserResource::make($user);
        $this->assertIsArray($resource);
        $this->assertArrayHasKey('id', $resource);
        $this->assertArrayHasKey('name', $resource);
        $this->assertArrayHasKey('email', $resource);
    }

    public function testUserResourceCollection(): void
    {
        $this->ensureTablesCreated();
        $user = (new UserRepository())->create([
            'name' => 'Collection User',
            'email' => 'coll-' . uniqid() . '@test.com',
            'password' => password_hash('secret', PASSWORD_BCRYPT),
            'status' => 1,
            'role' => 'user',
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        $collection = UserResource::collection([$user->toArray()]);
        $this->assertIsArray($collection);
        $this->assertCount(1, $collection);
    }

    public function testProductResourceMake(): void
    {
        $this->ensureTablesCreated();
        $product = (new ProductRepository())->store([
            'name' => 'Res Product',
            'price' => 10.00,
            'stock' => 5,
            'status' => 'active',
            'user_id' => 1,
        ]);
        $resource = ProductResource::make($product);
        $this->assertIsArray($resource);
        $this->assertArrayHasKey('id', $resource);
        $this->assertArrayHasKey('name', $resource);
    }

    public function testProductResourceCollection(): void
    {
        $this->ensureTablesCreated();
        $product = (new ProductRepository())->store([
            'name' => 'Coll Product',
            'price' => 15.00,
            'stock' => 3,
            'status' => 'active',
            'user_id' => 1,
        ]);
        $collection = ProductResource::collection([$product->toArray()]);
        $this->assertIsArray($collection);
        $this->assertCount(1, $collection);
    }

    public function testOrderResourceMake(): void
    {
        $this->ensureTablesCreated();
        $order = (new OrderRepository())->store([
            'customer_name' => 'Res Order',
            'customer_email' => 'resorder@test.com',
            'items' => json_encode([['product_id' => 1, 'price' => 10, 'quantity' => 1]]),
            'total' => 10.00,
            'status' => 'pending',
            'user_id' => 1,
        ]);
        $resource = OrderResource::make($order);
        $this->assertIsArray($resource);
        $this->assertArrayHasKey('id', $resource);
    }

    public function testOrderResourceCollection(): void
    {
        $this->ensureTablesCreated();
        $order = (new OrderRepository())->store([
            'customer_name' => 'Coll Order',
            'customer_email' => 'collorder@test.com',
            'items' => json_encode([['product_id' => 1, 'price' => 10, 'quantity' => 1]]),
            'total' => 10.00,
            'status' => 'pending',
            'user_id' => 1,
        ]);
        $collection = OrderResource::collection([$order->toArray()]);
        $this->assertIsArray($collection);
        $this->assertCount(1, $collection);
    }

    public function testCategoryResourceMake(): void
    {
        $this->ensureTablesCreated();
        $cat = (new CategoryRepository())->store([
            'name' => 'Res Category ' . uniqid(),
            'is_active' => 1,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        $resource = CategoryResource::make($cat);
        $this->assertIsArray($resource);
        $this->assertArrayHasKey('id', $resource);
    }

    public function testTagResourceMake(): void
    {
        $this->ensureTablesCreated();
        $tag = (new TagRepository())->store([
            'name' => 'Res Tag ' . uniqid(),
            'is_active' => 1,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        $resource = TagResource::make($tag);
        $this->assertIsArray($resource);
        $this->assertArrayHasKey('id', $resource);
    }

    public function testPostResourceMake(): void
    {
        $this->ensureTablesCreated();
        $post = (new PostRepository())->store([
            'title' => 'Res Post',
            'body' => 'Body with enough content.',
            'locale' => 'en',
            'status' => 'draft',
            'user_id' => 1,
        ]);
        $resource = PostResource::make($post);
        $this->assertIsArray($resource);
        $this->assertArrayHasKey('id', $resource);
    }

    public function testPostResourceCollection(): void
    {
        $this->ensureTablesCreated();
        $post = (new PostRepository())->store([
            'title' => 'Coll Post',
            'body' => 'Body for collection.',
            'locale' => 'en',
            'status' => 'draft',
            'user_id' => 1,
        ]);
        $collection = PostResource::collection([$post->toArray()]);
        $this->assertIsArray($collection);
        $this->assertCount(1, $collection);
    }

    // ─── Role ────────────────────────────────────────────────────────

    public function testRoleConstants(): void
    {
        $this->assertSame('admin', Role::ADMIN);
        $this->assertSame('user', Role::USER);
    }
}
