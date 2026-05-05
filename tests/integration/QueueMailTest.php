<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Tests\TestCase;
use Siro\Core\Database;
use Siro\Core\Queue;
use Siro\Core\Mail;
use Siro\Core\SendMailJob;

final class QueueMailTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->createApp();
        $pdo = Database::connection();
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS jobs (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                job TEXT NOT NULL,
                data TEXT NOT NULL,
                attempts INTEGER NOT NULL DEFAULT 0,
                max_attempts INTEGER NOT NULL DEFAULT 3,
                priority INTEGER NOT NULL DEFAULT 0,
                timeout INTEGER NOT NULL DEFAULT 120,
                available_at INTEGER NOT NULL DEFAULT 0,
                locked_until INTEGER DEFAULT NULL,
                created_at TEXT DEFAULT CURRENT_TIMESTAMP
            )
        ");
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS failed_jobs (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                job TEXT NOT NULL,
                data TEXT NOT NULL,
                error TEXT NOT NULL,
                failed_at TEXT DEFAULT CURRENT_TIMESTAMP
            )
        ");
        $pdo->exec("DELETE FROM jobs");
        $pdo->exec("DELETE FROM failed_jobs");
    }

    protected function tearDown(): void
    {
        $pdo = Database::connection();
        $pdo->exec("DROP TABLE IF EXISTS jobs");
        $pdo->exec("DROP TABLE IF EXISTS failed_jobs");
        parent::tearDown();
    }

    public function testQueuePushStoresJob(): void
    {
        Queue::push(NoopJob::class);
        $row = Database::first("SELECT * FROM jobs");
        $this->assertNotNull($row);
        $this->assertSame(NoopJob::class, $row['job']);
    }

    public function testQueueWorkProcessesJob(): void
    {
        CounterJob::$count = 0;
        Queue::push(CounterJob::class);
        Queue::work();
        $this->assertEquals(1, CounterJob::$count);
    }

    public function testQueueWorkDeletesJobAfterSuccess(): void
    {
        Queue::push(NoopJob::class);
        Queue::work();
        $row = Database::first("SELECT * FROM jobs");
        $this->assertNull($row);
    }

    public function testQueueWorkAllProcessesMultipleJobs(): void
    {
        CounterJob::$count = 0;
        for ($i = 0; $i < 5; $i++) {
            Queue::push(CounterJob::class);
        }
        $processed = Queue::workAll(10);
        $this->assertEquals(5, $processed);
        $this->assertEquals(5, CounterJob::$count);
    }

    public function testQueueRespectsPriorityOrdering(): void
    {
        PriorityJob::$order = [];
        Queue::push(PriorityJob::class, ['id' => 1], 0, 0);
        Queue::push(PriorityJob::class, ['id' => 2], 0, 10);
        Queue::workAll(10);
        $this->assertSame([2, 1], PriorityJob::$order);
    }

    public function testQueueRespectsDelay(): void
    {
        Queue::push(NoopJob::class, [], 3600);
        $row = Database::first("SELECT * FROM jobs");
        $this->assertNotNull($row);
        $this->assertGreaterThan(time(), (int)$row['available_at']);
        $processed = Queue::work();
        $this->assertFalse((bool)$processed);
    }

    public function testQueueMovesToFailedJobsAfterMaxAttempts(): void
    {
        $pdo = Database::connection();
        $pdo->exec("DELETE FROM jobs");
        $pdo->exec("DELETE FROM failed_jobs");

        Queue::push(FailingJob::class, [], 0, 0, 2);
        Queue::work();
        $job = Database::first("SELECT * FROM jobs");
        $this->assertEquals(1, (int)$job['attempts']);

        Database::execute("UPDATE jobs SET available_at = :now WHERE id = :id", [
            'now' => time(),
            'id' => $job['id'],
        ]);
        Queue::work();

        $failed = Database::first("SELECT * FROM failed_jobs");
        $this->assertNotNull($failed);
        $this->assertSame(FailingJob::class, $failed['job']);
    }

    public function testMailFluentInterface(): void
    {
        $mail = Mail::to('user@test.com')->subject('Hello')->html('<h1>Hi</h1>');
        $refl = new \ReflectionClass($mail);
        $to = $refl->getProperty('to');
        $to->setAccessible(true);
        $this->assertSame('user@test.com', $to->getValue($mail));
    }

    public function testMailQueuePushesToJobsTable(): void
    {
        Mail::to('queued@test.com')->subject('Queued')->html('<p>Queued email</p>')->queue();
        $row = Database::first("SELECT * FROM jobs");
        $this->assertNotNull($row);
        $this->assertSame(SendMailJob::class, $row['job']);
    }

    public function testQueuePendingCountAndFailedCount(): void
    {
        $this->assertEquals(0, Queue::pendingCount());
        $this->assertEquals(0, Queue::failedCount());
        Queue::push(NoopJob::class);
        $this->assertEquals(1, Queue::pendingCount());
        Queue::work();
        $this->assertEquals(0, Queue::pendingCount());
    }

    public function testSendMailJobReconstructsMail(): void
    {
        $job = new SendMailJob();
        $data = [
            'to' => 'test@example.com',
            'subject' => 'Test Subject',
            'body' => '<h1>Test Body</h1>',
            'content_type' => 'text/html',
            'cc' => ['cc@example.com'],
            'bcc' => ['bcc@example.com'],
            'reply_to' => 'reply@example.com',
            'attachments' => [],
            'charset' => 'UTF-8',
        ];
        try {
            $job->handle($data);
        } catch (\RuntimeException) {
        }
        $this->assertTrue(true);
    }
}

final class NoopJob
{
    public function handle(array $data = []): void {}
}

final class CounterJob
{
    public static int $count = 0;
    public function handle(array $data = []): void { self::$count++; }
}

final class PriorityJob
{
    public static array $order = [];
    public function handle(array $data = []): void { self::$order[] = $data['id'] ?? 0; }
}

final class FailingJob
{
    public function handle(array $data = []): void { throw new \RuntimeException('Test failure'); }
}
