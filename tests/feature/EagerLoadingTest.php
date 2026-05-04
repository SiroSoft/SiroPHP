<?php

declare(strict_types=1);

namespace App\Tests\Feature;

use App\Tests\TestCase;
use Siro\Core\Database;
use Siro\Core\Model;

final class EagerLoadingTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->createApp();
        $db = new Database();
        $db->execute('DROP TABLE IF EXISTS test_eager_posts');
        $db->execute('DROP TABLE IF EXISTS test_eager_users');
        $db->execute('CREATE TABLE test_eager_users (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT NOT NULL, email TEXT NOT NULL)');
        $db->execute('CREATE TABLE test_eager_posts (id INTEGER PRIMARY KEY AUTOINCREMENT, user_id INTEGER NOT NULL, title TEXT NOT NULL, body TEXT)');
        for ($i = 1; $i <= 5; $i++) {
            $db->execute('INSERT INTO test_eager_users (name, email) VALUES (?, ?)', ["User {$i}", "user{$i}@test.com"]);
            for ($j = 1; $j <= 3; $j++) {
                $db->execute('INSERT INTO test_eager_posts (user_id, title, body) VALUES (?, ?, ?)', [$i, "Post {$j}", "Content {$j}"]);
            }
        }
    }

    protected function tearDown(): void
    {
        $db = new Database();
        $db->execute('DROP TABLE IF EXISTS test_eager_posts');
        $db->execute('DROP TABLE IF EXISTS test_eager_users');
        parent::tearDown();
    }

    private function userModel(): string
    {
        return 'App\Tests\Feature\EagerTestUser';
    }

    public function testModelWithMethodExists(): void
    {
        $this->assertTrue(method_exists($this->userModel(), 'with'));
    }

    public function testEagerLoadReturnsResults(): void
    {
        $users = EagerTestUser::with('posts')->get();
        $this->assertCount(5, $users);
    }

    public function testEagerLoadWithLimit(): void
    {
        $users = EagerTestUser::limit(2)->get();
        $this->assertCount(2, $users);
    }

    public function testEagerLoadWithWhere(): void
    {
        $users = EagerTestUser::where('name', 'LIKE', '%User 1%')->get();
        $this->assertCount(1, $users);
    }

    public function testEagerLoadWithNoResults(): void
    {
        $users = EagerTestUser::where('email', '=', 'nonexistent@test.com')->get();
        $this->assertCount(0, $users);
    }
}

class EagerTestUser extends Model
{
    protected string $table = 'test_eager_users';
    protected array $fillable = ['name', 'email'];
    public function posts() { return $this->hasMany(EagerTestPost::class, 'user_id'); }
}

class EagerTestPost extends Model
{
    protected string $table = 'test_eager_posts';
    protected array $fillable = ['user_id', 'title', 'body'];
}
