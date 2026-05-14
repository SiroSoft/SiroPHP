<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Tests\TestCase;
use Siro\Core\Database;
use Siro\Core\Model;

final class DatabaseTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->createApp();
        $db = new Database();
        $db->execute('DROP TABLE IF EXISTS test_integration_users');
        $db->execute('CREATE TABLE IF NOT EXISTS test_integration_users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            email TEXT NOT NULL UNIQUE,
            age INTEGER,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP
        )');
    }

    protected function tearDown(): void
    {
        $db = new Database();
        $db->execute('DROP TABLE IF EXISTS test_integration_users');
        parent::tearDown();
    }

    public function testInsertSingleRecord(): void
    {
        $db = new Database();
        $result = $db->execute(
            'INSERT INTO test_integration_users (name, email, age) VALUES (:name, :email, :age)',
            ['name' => 'John Doe', 'email' => 'john@test.com', 'age' => 30]
        );
        $this->assertGreaterThan(0, $result);
    }

    public function testSelectRecords(): void
    {
        $db = new Database();
        $db->execute('INSERT INTO test_integration_users (name, email, age) VALUES (:name, :email, :age)', ['name' => 'John Doe', 'email' => 'john@test.com', 'age' => 30]);
        $users = $db->select('SELECT * FROM test_integration_users WHERE email = :email', ['email' => 'john@test.com']);
        $this->assertCount(1, $users);
        $this->assertSame('John Doe', $users[0]['name']);
    }

    public function testUpdateRecord(): void
    {
        $db = new Database();
        $db->execute('INSERT INTO test_integration_users (name, email, age) VALUES (:name, :email, :age)', ['name' => 'John Doe', 'email' => 'john@test.com', 'age' => 30]);
        $db->execute('UPDATE test_integration_users SET age = :age WHERE email = :email', ['age' => 31, 'email' => 'john@test.com']);
        $users = $db->select('SELECT * FROM test_integration_users WHERE email = :email', ['email' => 'john@test.com']);
        $age = $users[0]['age'] ?? 0;
        /** @var int|string $age */
        $this->assertEquals(31, (int) $age);
    }

    public function testDeleteRecord(): void
    {
        $db = new Database();
        $db->execute('INSERT INTO test_integration_users (name, email, age) VALUES (:name, :email, :age)', ['name' => 'John Doe', 'email' => 'john@test.com', 'age' => 30]);
        $db->execute('DELETE FROM test_integration_users WHERE email = :email', ['email' => 'john@test.com']);
        $users = $db->select('SELECT * FROM test_integration_users WHERE email = :email', ['email' => 'john@test.com']);
        $this->assertCount(0, $users);
    }

    public function testTransactionCommitWorks(): void
    {
        $db = new Database();
        $result = Database::transaction(function () use ($db) {
            $db->execute('INSERT INTO test_integration_users (name, email, age) VALUES (:name, :email, :age)', ['name' => 'Transaction User', 'email' => 'transaction@test.com', 'age' => 25]);
            return true;
        });
        $this->assertTrue($result);
        $users = $db->select('SELECT * FROM test_integration_users WHERE email = :email', ['email' => 'transaction@test.com']);
        $this->assertCount(1, $users);
    }

    public function testTransactionRollbackOnException(): void
    {
        $db = new Database();
        try {
            Database::transaction(function () use ($db) {
                $db->execute('INSERT INTO test_integration_users (name, email, age) VALUES (:name, :email, :age)', ['name' => 'Rollback User', 'email' => 'rollback@test.com', 'age' => 25]);
                throw new \RuntimeException('Intentional error for rollback test');
            });
        } catch (\RuntimeException) {
            $users = $db->select('SELECT * FROM test_integration_users WHERE email = :email', ['email' => 'rollback@test.com']);
            $this->assertCount(0, $users);
        }
    }

    public function testHandleNullValues(): void
    {
        $db = new Database();
        $db->execute('INSERT INTO test_integration_users (name, email, age) VALUES (:name, :email, :age)', ['name' => 'No Age User', 'email' => 'noage@test.com', 'age' => null]);
        $users = $db->select('SELECT * FROM test_integration_users WHERE email = :email', ['email' => 'noage@test.com']);
        $this->assertCount(1, $users);
    }

    public function testHandleSpecialCharacters(): void
    {
        $db = new Database();
        $db->execute('INSERT INTO test_integration_users (name, email, age) VALUES (:name, :email, :age)', ['name' => "O'Brien", 'email' => 'obrien@test.com', 'age' => 30]);
        $users = $db->select('SELECT * FROM test_integration_users WHERE name = :name', ['name' => "O'Brien"]);
        $this->assertCount(1, $users);
    }
}
