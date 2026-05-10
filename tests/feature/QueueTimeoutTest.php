<?php

declare(strict_types=1);

namespace App\Tests\Feature;

use App\Tests\TestCase;
use Siro\Core\Queue;

final class QueueTimeoutTest extends TestCase
{
    public function testQueueWorkMethodExists(): void { $this->assertTrue(method_exists(Queue::class, 'work')); } // @phpstan-ignore function.alreadyNarrowedType
    public function testQueueWorkAllMethodExists(): void { $this->assertTrue(method_exists(Queue::class, 'workAll')); } // @phpstan-ignore function.alreadyNarrowedType
    public function testQueuePendingCountMethodExists(): void { $this->assertTrue(method_exists(Queue::class, 'pendingCount')); } // @phpstan-ignore function.alreadyNarrowedType
    public function testQueueFailedCountMethodExists(): void { $this->assertTrue(method_exists(Queue::class, 'failedCount')); } // @phpstan-ignore function.alreadyNarrowedType
}
