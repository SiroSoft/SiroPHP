<?php

declare(strict_types=1);

namespace App\Tests\Feature;

use App\Tests\TestCase;
use Siro\Core\Database;
use Siro\Core\Model;

final class MassAssignmentTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->createApp();
        $db = new Database();
        $db->execute('DROP TABLE IF EXISTS ma_test_users');
        $db->execute('CREATE TABLE ma_test_users (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT NOT NULL, email TEXT NOT NULL, role TEXT DEFAULT "user", is_admin INTEGER DEFAULT 0, created_at TEXT DEFAULT CURRENT_TIMESTAMP)');
    }

    protected function tearDown(): void
    {
        $db = new Database();
        $db->execute('DROP TABLE IF EXISTS ma_test_users');
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
        $fresh = $model::find($user->id);
        $this->assertSame('Alice Updated', $fresh->name);
    }
}
