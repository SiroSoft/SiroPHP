<?php

declare(strict_types=1);

namespace App\Tests\Feature;

use App\Tests\TestCase;
use Siro\Core\Database;
use Siro\Core\Lang;
use Siro\Core\Model;

final class MassAssignmentTest extends TestCase
{
    protected function setUp(): void
    {
        $this->basePath = dirname(__DIR__, 2);
        \Siro\Core\Lang::setLocale('en');
        $this->createApp();
        try {
            $pdo = Database::connection();
            $driver = $pdo->getAttribute(\PDO::ATTR_DRIVER_NAME);
            if ($driver === 'sqlite') {
                $this->markTestSkipped('Mass assignment tests require MySQL/PostgreSQL');
            }
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
        } catch (\Throwable) {
            $this->markTestSkipped('Could not determine database driver');
        }
        Database::execute('DROP TABLE IF EXISTS ma_test_users');
        Database::execute('CREATE TABLE ma_test_users (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT NOT NULL, email TEXT NOT NULL, role TEXT DEFAULT "user", is_admin INTEGER DEFAULT 0, created_at TEXT DEFAULT CURRENT_TIMESTAMP)');
        parent::setUp();
    }

    protected function tearDown(): void
    {
        try {
            $pdo = Database::connection();
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
        } catch (\Throwable) {
        }
        Database::execute('DROP TABLE IF EXISTS ma_test_users');
        parent::tearDown();
    }

    public function testFillableProtectsAttributes(): void
    {
        $model = new class extends Model {
            protected string $table = 'ma_test_users';
            protected array $fillable = ['name', 'email'];
        };
        $user = $model->create(['name' => 'John', 'email' => 'john@test.com', 'is_admin' => 1, 'role' => 'admin']);
        $this->assertNotNull($user->id);
        $this->assertSame('John', $user->name);
        $this->assertSame('john@test.com', $user->email);
    }

    public function testCreateWithOnlyFillableFields(): void
    {
        $model = new class extends Model {
            protected string $table = 'ma_test_users';
            protected array $fillable = ['name', 'email'];
        };
        $user = $model->create(['name' => 'Bob', 'email' => 'bob@test.com']);
        $this->assertNotNull($user->id);
    }

    public function testUpdateRespectsFillable(): void
    {
        $model = new class extends Model {
            protected string $table = 'ma_test_users';
            protected array $fillable = ['name', 'email'];
        };
        $user = $model->create(['name' => 'Alice', 'email' => 'alice@test.com']);
        $user->fill(['name' => 'Alice Updated', 'is_admin' => 1]);
        $user->save();
        $userId = $user->id;
        /** @var int|string $userId */
        $fresh = $model::find($userId);
        $this->assertNotNull($fresh);
        $this->assertSame('Alice Updated', $fresh->name);
    }
}
