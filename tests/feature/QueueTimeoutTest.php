<?php

declare(strict_types=1);

namespace App\Tests\Feature;

use App\Tests\TestCase;
use Siro\Core\Queue;

final class QueueTimeoutTest extends TestCase
{
    public function testQueueWorkMethodExists(): void { $this->assertTrue(method_exists(Queue::class, 'work')); }
    public function testQueueWorkAllMethodExists(): void { $this->assertTrue(method_exists(Queue::class, 'workAll')); }
    public function testQueuePendingCountMethodExists(): void { $this->assertTrue(method_exists(Queue::class, 'pendingCount')); }
    public function testQueueFailedCountMethodExists(): void { $this->assertTrue(method_exists(Queue::class, 'failedCount')); }
}
