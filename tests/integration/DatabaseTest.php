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
            'INSERT INTO test_integration_users (name, email, age) VALUES (?, ?, ?)',
            ['John Doe', 'john@test.com', 30]
        );
        $this->assertGreaterThan(0, $result);
    }

    public function testSelectRecords(): void
    {
        $db = new Database();
        $db->execute('INSERT INTO test_integration_users (name, email, age) VALUES (?, ?, ?)', ['John Doe', 'john@test.com', 30]);
        $users = $db->select('SELECT * FROM test_integration_users WHERE email = ?', ['john@test.com']);
        $this->assertCount(1, $users);
        $this->assertSame('John Doe', $users[0]['name']);
    }

    public function testUpdateRecord(): void
    {
        $db = new Database();
        $db->execute('INSERT INTO test_integration_users (name, email, age) VALUES (?, ?, ?)', ['John Doe', 'john@test.com', 30]);
        $db->execute('UPDATE test_integration_users SET age = ? WHERE email = ?', [31, 'john@test.com']);
        $users = $db->select('SELECT * FROM test_integration_users WHERE email = ?', ['john@test.com']);
        $this->assertEquals(31, (int)$users[0]['age']);
    }

    public function testDeleteRecord(): void
    {
        $db = new Database();
        $db->execute('INSERT INTO test_integration_users (name, email, age) VALUES (?, ?, ?)', ['John Doe', 'john@test.com', 30]);
        $db->execute('DELETE FROM test_integration_users WHERE email = ?', ['john@test.com']);
        $users = $db->select('SELECT * FROM test_integration_users WHERE email = ?', ['john@test.com']);
        $this->assertCount(0, $users);
    }

    public function testTransactionCommitWorks(): void
    {
        $db = new Database();
        $result = Database::transaction(function () use ($db) {
            $db->execute('INSERT INTO test_integration_users (name, email, age) VALUES (?, ?, ?)', ['Transaction User', 'transaction@test.com', 25]);
            return true;
        });
        $this->assertTrue($result);
        $users = $db->select('SELECT * FROM test_integration_users WHERE email = ?', ['transaction@test.com']);
        $this->assertCount(1, $users);
    }

    public function testTransactionRollbackOnException(): void
    {
        $db = new Database();
        try {
            Database::transaction(function () use ($db) {
                $db->execute('INSERT INTO test_integration_users (name, email, age) VALUES (?, ?, ?)', ['Rollback User', 'rollback@test.com', 25]);
                throw new \RuntimeException('Intentional error for rollback test');
            });
        } catch (\RuntimeException) {
            $users = $db->select('SELECT * FROM test_integration_users WHERE email = ?', ['rollback@test.com']);
            $this->assertCount(0, $users);
        }
    }

    public function testHandleNullValues(): void
    {
        $db = new Database();
        $db->execute('INSERT INTO test_integration_users (name, email, age) VALUES (?, ?, ?)', ['No Age User', 'noage@test.com', null]);
        $users = $db->select('SELECT * FROM test_integration_users WHERE email = ?', ['noage@test.com']);
        $this->assertCount(1, $users);
    }

    public function testHandleSpecialCharacters(): void
    {
        $db = new Database();
        $db->execute('INSERT INTO test_integration_users (name, email, age) VALUES (?, ?, ?)', ["O'Brien", "obrien@test.com", 30]);
        $users = $db->select('SELECT * FROM test_integration_users WHERE name = ?', ["O'Brien"]);
        $this->assertCount(1, $users);
    }
}
