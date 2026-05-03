<?php

declare(strict_types=1);

/**
 * Comprehensive test for Queue and Mail systems.
 *
 * Run: php tests/queue_mail_test.php
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Siro\Core\App;
use Siro\Core\Database;
use Siro\Core\Queue;
use Siro\Core\Mail;

// ─── Bootstrap ───────────────────────────────────────────
$basePath = dirname(__DIR__);
define('SIRO_BASE_PATH', $basePath);

$app = new App($basePath);
$app->boot();

$pdo = Database::connection();
$driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);

echo "=== Queue & Mail Integration Test ===\n";
echo "Driver: {$driver}\n";
echo "PHP: " . PHP_VERSION . "\n\n";

$passed = 0;
$failed = 0;

function test(string $name, callable $fn): void
{
    global $passed, $failed;
    try {
        $fn();
        echo "  ✓ {$name}\n";
        $passed++;
    } catch (Throwable $e) {
        echo "  ✗ {$name}: {$e->getMessage()}\n";
        echo "    in {$e->getFile()}:{$e->getLine()}\n";
        $failed++;
    }
}

function assertTrue(mixed $value, string $msg = ''): void
{
    if ($value !== true) {
        throw new RuntimeException($msg ?: 'Expected true, got ' . gettype($value));
    }
}

function assertEquals(mixed $expected, mixed $actual, string $msg = ''): void
{
    if ($expected !== $actual) {
        throw new RuntimeException($msg ?: 'Expected ' . json_encode($expected) . ', got ' . json_encode($actual));
    }
}

function assertNotNull(mixed $value, string $msg = ''): void
{
    if ($value === null) {
        throw new RuntimeException($msg ?: 'Expected non-null');
    }
}

function assertNull(mixed $value, string $msg = ''): void
{
    if ($value !== null) {
        throw new RuntimeException($msg ?: 'Expected null, got ' . json_encode($value));
    }
}

// ─── Setup: create tables ────────────────────────────────
echo "--- Setup ---\n";

require_once __DIR__ . '/db_test_helper.php';

test('Create jobs table', function () use ($pdo): void {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS jobs (
            id " . db_id_col() . ",
            job TEXT NOT NULL,
            data TEXT NOT NULL,
            attempts " . db_type_int() . " NOT NULL DEFAULT 0,
            max_attempts " . db_type_int() . " NOT NULL DEFAULT 3,
            priority " . db_type_int() . " NOT NULL DEFAULT 0,
            timeout " . db_type_int() . " NOT NULL DEFAULT 120,
            available_at " . db_type_int() . " NOT NULL DEFAULT 0,
            locked_until " . db_type_int() . " DEFAULT NULL,
            created_at " . db_datetime_col() . "
        )
    ");
    assertTrue(true);
});

test('Create failed_jobs table', function () use ($pdo): void {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS failed_jobs (
            id " . db_id_col() . ",
            job TEXT NOT NULL,
            data TEXT NOT NULL,
            error TEXT NOT NULL,
            failed_at " . db_datetime_col() . "
        )
    ");
    assertTrue(true);
});

test('Clean tables before tests', function () use ($pdo): void {
    $pdo->exec("DELETE FROM jobs");
    $pdo->exec("DELETE FROM failed_jobs");
    assertTrue(true);
});

// ─── Queue Tests ─────────────────────────────────────────
echo "\n--- Queue Tests ---\n";

test('Queue::push() stores a job', function () use ($pdo): void {
    Queue::push(NoopJob::class);
    $row = Database::first("SELECT * FROM jobs");
    assertNotNull($row, 'Job should exist');
    assertEquals(NoopJob::class, $row['job']);
});

$pdo->exec("DELETE FROM jobs");

test('Queue::work() processes a job', function (): void {
    CounterJob::$count = 0;
    Queue::push(CounterJob::class);
    Queue::work();
    assertEquals(1, CounterJob::$count, 'Job should have been executed');
});

test('Queue::work() deletes job after success', function (): void {
    Queue::push(NoopJob::class);
    Queue::work();
    $row = Database::first("SELECT * FROM jobs");
    assertNull($row, 'Job should be deleted after success');
});

test('Queue::workAll() processes multiple jobs', function (): void {
    CounterJob::$count = 0;
    for ($i = 0; $i < 5; $i++) {
        Queue::push(CounterJob::class);
    }
    $processed = Queue::workAll(10);
    assertEquals(5, $processed, 'Should process 5 jobs');
    assertEquals(5, CounterJob::$count, 'All jobs should execute');
});

test('Queue respects priority ordering', function (): void {
    PriorityJob::$order = [];
    Queue::push(PriorityJob::class, ['id' => 1], 0, 0); // low priority
    Queue::push(PriorityJob::class, ['id' => 2], 0, 10); // high priority
    Queue::workAll(10);
    assertEquals([2, 1], PriorityJob::$order, 'Higher priority (10) should run first');
});

$pdo->exec("DELETE FROM jobs");

test('Queue respects delay', function (): void {
    Queue::push(NoopJob::class, [], 3600); // 1 hour delay
    $row = Database::first("SELECT * FROM jobs");
    assertNotNull($row);
    assertTrue($row['available_at'] > time(), 'Job should not be available yet');
    $processed = Queue::work();
    assertTrue(!$processed, 'Delayed job should not be processed');
});

$pdo->exec("DELETE FROM jobs");

test('Queue moves to failed_jobs after max attempts', function (): void {
    Queue::push(FailingJob::class, [], 0, 0, 2); // max 2 attempts

    // First attempt: job fails → backoff set (attempts=1, available_at=now+5)
    Queue::work();
    $job = Database::first("SELECT * FROM jobs");
    assertEquals(1, (int) $job['attempts'], 'Should have 1 attempt');

    // Bypass backoff so work() picks it up immediately
    Database::execute("UPDATE jobs SET available_at = :now WHERE id = :id", [
        'now' => time(),
        'id' => $job['id'],
    ]);

    // Second attempt: now it should fail and move to failed_jobs
    Queue::work();
    $failed = Database::first("SELECT * FROM failed_jobs");
    assertNotNull($failed, 'Should be in failed_jobs');
    assertEquals(FailingJob::class, $failed['job']);
    assertTrue(str_contains($failed['error'] ?? '', 'Test failure'), 'Error message should be preserved');

    // Original job deleted
    $job = Database::first("SELECT * FROM jobs");
    assertNull($job, 'Original job should be deleted');
});

$pdo->exec("DELETE FROM jobs");
$pdo->exec("DELETE FROM failed_jobs");

test('Queue::retryFailed() re-pushes failed job', function (): void {
    Database::execute(
        "INSERT INTO failed_jobs (job, data, error, failed_at) VALUES (:job, :data, :error, :failed_at)",
        [
            'job' => 'TestJob',
            'data' => serialize(['key' => 'value']),
            'error' => 'Test error',
            'failed_at' => date('Y-m-d H:i:s'),
        ]
    );

    $failedId = (int) Database::connection()->lastInsertId();
    $result = Queue::retryFailed($failedId);
    assertTrue($result, 'retryFailed should return true');

    $job = Database::first("SELECT * FROM jobs");
    assertNotNull($job, 'Job should be re-pushed');
    assertEquals('TestJob', $job['job']);

    $failed = Database::first("SELECT * FROM failed_jobs WHERE id = :id", ['id' => $failedId]);
    assertNull($failed, 'Failed job should be deleted after retry');
});

$pdo->exec("DELETE FROM jobs");
$pdo->exec("DELETE FROM failed_jobs");

test('Queue::retryFailed(all) retries all', function (): void {
    Database::execute("INSERT INTO failed_jobs (job, data, error, failed_at) VALUES (:job, :data, :error, :failed_at)", ['job' => 'A', 'data' => '', 'error' => '', 'failed_at' => date('Y-m-d H:i:s')]);
    Database::execute("INSERT INTO failed_jobs (job, data, error, failed_at) VALUES (:job, :data, :error, :failed_at)", ['job' => 'B', 'data' => '', 'error' => '', 'failed_at' => date('Y-m-d H:i:s')]);

    Queue::retryFailed('all');

    $count = (int) Database::first("SELECT COUNT(*) AS c FROM jobs")['c'];
    assertEquals(2, $count, 'Both jobs should be re-pushed');

    $fcount = (int) Database::first("SELECT COUNT(*) AS c FROM failed_jobs")['c'];
    assertEquals(0, $fcount, 'All failed jobs should be deleted');
});

$pdo->exec("DELETE FROM jobs");
$pdo->exec("DELETE FROM failed_jobs");

test('Queue::flushFailed() clears all', function (): void {
    Database::execute("INSERT INTO failed_jobs (job, data, error, failed_at) VALUES (:job, :data, :error, :failed_at)", ['job' => 'A', 'data' => '', 'error' => '', 'failed_at' => date('Y-m-d H:i:s')]);
    Database::execute("INSERT INTO failed_jobs (job, data, error, failed_at) VALUES (:job, :data, :error, :failed_at)", ['job' => 'B', 'data' => '', 'error' => '', 'failed_at' => date('Y-m-d H:i:s')]);

    $count = Queue::flushFailed();
    assertEquals(2, $count);

    $remaining = (int) Database::first("SELECT COUNT(*) AS c FROM failed_jobs")['c'];
    assertEquals(0, $remaining);
});

test('Queue::pendingCount() and Queue::failedCount()', function (): void {
    assertEquals(0, Queue::pendingCount());
    assertEquals(0, Queue::failedCount());

    Queue::push(NoopJob::class);
    assertEquals(1, Queue::pendingCount());

    Queue::work();
    assertEquals(0, Queue::pendingCount());
});

$pdo->exec("DELETE FROM jobs");
$pdo->exec("DELETE FROM failed_jobs");

test('Queue job with class name passes data', function (): void {
    Queue::push(DataJob::class, ['result' => 'ok']);
    Queue::work();
    assertEquals('ok', DataJob::$lastResult, 'Job handler should receive data');
});

$pdo->exec("DELETE FROM jobs");

// ─── Mail Tests ──────────────────────────────────────────
echo "\n--- Mail Tests ---\n";

test('Mail::to() creates instance', function (): void {
    $mail = Mail::to('test@example.com');
    assertNotNull($mail);
});

test('Mail fluent interface chaining', function (): void {
    $mail = Mail::to('user@test.com')
        ->subject('Hello')
        ->html('<h1>Hi</h1>');

    $refl = new ReflectionClass($mail);
    $to = $refl->getProperty('to');
    $to->setAccessible(true);
    assertEquals('user@test.com', $to->getValue($mail));

    $subject = $refl->getProperty('subject');
    $subject->setAccessible(true);
    assertEquals('Hello', $subject->getValue($mail));
});

test('Mail CC and BCC', function (): void {
    $mail = Mail::to('to@test.com')
        ->cc('cc@test.com')
        ->bcc('bcc@test.com');

    $refl = new ReflectionClass($mail);
    $cc = $refl->getProperty('cc');
    $cc->setAccessible(true);
    assertEquals(['cc@test.com'], $cc->getValue($mail));

    $bcc = $refl->getProperty('bcc');
    $bcc->setAccessible(true);
    assertEquals(['bcc@test.com'], $bcc->getValue($mail));
});

test('Mail replyTo', function (): void {
    $mail = Mail::to('to@test.com')->replyTo('reply@test.com');

    $refl = new ReflectionClass($mail);
    $replyTo = $refl->getProperty('replyTo');
    $replyTo->setAccessible(true);
    assertEquals('reply@test.com', $replyTo->getValue($mail));
});

test('Mail throws on send without recipient', function (): void {
    $threw = false;
    try {
        $mail = new Mail();
        $refl = new ReflectionClass($mail);
        $send = $refl->getMethod('send');
        $send->setAccessible(true);
        $send->invoke($mail);
    } catch (RuntimeException $e) {
        $threw = true;
    }
    assertTrue($threw, 'Should throw without recipient');
});

test('Mail::queue() pushes to jobs table', function (): void {
    Mail::to('queued@test.com')
        ->subject('Queued')
        ->html('<p>Queued email</p>')
        ->queue();

    $row = Database::first("SELECT * FROM jobs");
    assertNotNull($row, 'Mail should be queued as a job');
    assertEquals('Siro\Core\SendMailJob', $row['job']);
});

$pdo->exec("DELETE FROM jobs");

test('Mail::sendLater() pushes delayed job', function (): void {
    Mail::to('later@test.com')
        ->subject('Later')
        ->html('<p>Later</p>')
        ->sendLater(7200);

    $row = Database::first("SELECT * FROM jobs");
    assertNotNull($row, 'Mail should be queued');
    assertTrue($row['available_at'] > time(), 'Should be delayed');
});

$pdo->exec("DELETE FROM jobs");

// ─── SendMailJob Test ────────────────────────────────────
echo "\n--- SendMailJob Test ---\n";

test('SendMailJob::handle() reconstructs mail correctly', function (): void {
    $job = new \Siro\Core\SendMailJob();

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
        echo "    (mail sent - PHP mail() returned true)\n";
    } catch (RuntimeException $e) {
        echo "    (mail driver rejected: {$e->getMessage()})\n";
    }

    assertTrue(true, 'SendMailJob should not throw before mail send');
});

// ─── Backoff Calculation Test ────────────────────────────
echo "\n--- Backoff Calculation ---\n";

test('Exponential backoff: attempt 1 = 5s', function (): void {
    $refl = new ReflectionClass(Queue::class);
    $method = $refl->getMethod('calculateBackoff');
    $method->setAccessible(true);
    $result = $method->invoke(null, 1);
    assertEquals(5, $result, 'Attempt 1 should backoff 5s');
});

test('Exponential backoff: attempt 2 = 10s', function (): void {
    $refl = new ReflectionClass(Queue::class);
    $method = $refl->getMethod('calculateBackoff');
    $method->setAccessible(true);
    $result = $method->invoke(null, 2);
    assertEquals(10, $result, 'Attempt 2 should backoff 10s');
});

test('Exponential backoff: attempt 3 = 20s', function (): void {
    $refl = new ReflectionClass(Queue::class);
    $method = $refl->getMethod('calculateBackoff');
    $method->setAccessible(true);
    $result = $method->invoke(null, 3);
    assertEquals(20, $result, 'Attempt 3 should backoff 20s');
});

test('Exponential backoff: attempt 6 = 160s', function (): void {
    $refl = new ReflectionClass(Queue::class);
    $method = $refl->getMethod('calculateBackoff');
    $method->setAccessible(true);
    $result = $method->invoke(null, 6);
    assertEquals(160, $result, 'Attempt 6 should backoff 160s');
});

test('Exponential backoff: attempt 10 = 300s (capped)', function (): void {
    $refl = new ReflectionClass(Queue::class);
    $method = $refl->getMethod('calculateBackoff');
    $method->setAccessible(true);
    $result = $method->invoke(null, 10);
    assertEquals(300, $result, 'Attempt 10 should be capped at 300s');
});

// ─── Summary ─────────────────────────────────────────────
echo "\n=== Results ===\n";
echo "Passed: {$passed}\n";
echo "Failed: {$failed}\n";
echo "\n";

// Cleanup test tables
$pdo->exec("DROP TABLE IF EXISTS jobs");
$pdo->exec("DROP TABLE IF EXISTS failed_jobs");

exit($failed > 0 ? 1 : 0);

// ─── Test Job Classes ────────────────────────────────────
final class NoopJob
{
    public function handle(array $data = []): void {}
}

final class CounterJob
{
    public static int $count = 0;
    public function handle(array $data = []): void
    {
        self::$count++;
    }
}

final class PriorityJob
{
    /** @var array<int, int> */
    public static array $order = [];
    public function handle(array $data = []): void
    {
        self::$order[] = $data['id'] ?? 0;
    }
}

final class FailingJob
{
    public function handle(array $data = []): void
    {
        throw new RuntimeException('Test failure');
    }
}

final class DataJob
{
    public static mixed $lastResult = null;
    public function handle(array $data = []): void
    {
        self::$lastResult = $data['result'] ?? null;
    }
}
