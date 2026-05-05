<?php

declare(strict_types=1);

namespace App\Tests\Feature;

use App\Tests\TestCase;
use Siro\Core\Schedule;
use Siro\Core\ScheduleTask;

final class AdvancedCronTest extends TestCase
{
    private Schedule $schedule;

    protected function setUp(): void
    {
        parent::setUp();
        $this->schedule = new Schedule();
    }

    public function testScheduleHasCommandMethod(): void { $this->assertTrue(method_exists(Schedule::class, 'command')); }
    public function testScheduleHasCallMethod(): void { $this->assertTrue(method_exists(Schedule::class, 'call')); }
    public function testCommandReturnsScheduleTask(): void { $task = $this->schedule->command('test:cmd'); $this->assertInstanceOf(ScheduleTask::class, $task); }
    public function testCallReturnsScheduleTask(): void { $task = $this->schedule->call(function () {}); $this->assertInstanceOf(ScheduleTask::class, $task); }
    public function testScheduleTaskHasCronMethod(): void { $task = $this->schedule->command('test'); $this->assertTrue(method_exists($task, 'cron')); }
    public function testScheduleTaskHasDailyMethod(): void { $task = $this->schedule->command('test'); $this->assertTrue(method_exists($task, 'daily')); }
    public function testScheduleTaskHasHourlyMethod(): void { $task = $this->schedule->command('test'); $this->assertTrue(method_exists($task, 'hourly')); }
    public function testScheduleTaskHasEveryMinuteMethod(): void { $task = $this->schedule->command('test'); $this->assertTrue(method_exists($task, 'everyMinute')); }
    public function testScheduleTaskCronExpressionChainable(): void { $task = $this->schedule->command('test')->cron('*/5 * * * *'); $this->assertInstanceOf(ScheduleTask::class, $task); }
}
