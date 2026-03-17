<?php

namespace Tests\Unit\Queue;

use UnhandledMatchError;
use App\Abstracts\Job;
use App\Queue\QueueManager;
use App\Queue\Drivers\SyncDriver;
use App\Contracts\QueueDriver;
use PHPUnit\Framework\TestCase;

class QueueManagerTest extends TestCase
{
    public function test_resolves_sync_driver(): void
    {
        $manager = new QueueManager('sync');

        $this->assertInstanceOf(SyncDriver::class, $manager->getDriver());
    }

    public function test_push_executes_job_immediately_with_sync_driver(): void
    {
        $manager = new QueueManager('sync');
        $job     = new SyncTestJob();

        $manager->push($job);

        $this->assertTrue(SyncTestJob::$handled);
    }

    public function test_push_uses_job_queue_property(): void
    {
        $manager = new QueueManager('sync');
        $job     = new QueuedTestJob();

        $manager->push($job);

        $this->assertTrue(QueuedTestJob::$handled);
        $this->assertSame('emails', QueuedTestJob::$usedQueue);
    }

    public function test_push_falls_back_to_default_queue_when_empty(): void
    {
        $manager = new QueueManager('sync');
        $job     = new SyncTestJob();

        $manager->push($job);

        $this->assertSame('default', SyncTestJob::$usedQueue);
    }

    public function test_later_sets_delay_and_pushes(): void
    {
        $manager = new QueueManager('sync');
        $job     = new DelayTestJob();

        $manager->later(30, $job);

        $this->assertSame(30, DelayTestJob::$capturedDelay);
        $this->assertTrue(DelayTestJob::$handled);
    }

    public function test_invalid_driver_throws_unhandled_match_error(): void
    {
        $this->expectException(UnhandledMatchError::class);

        new QueueManager('redis');
    }
}

class SyncTestJob extends Job
{
    public static bool $handled   = false;
    public static string $usedQueue = '';

    public function handle(): void
    {
        self::$handled   = true;
        self::$usedQueue = $this->queue !== '' ? $this->queue : 'default';
    }
}

class QueuedTestJob extends Job
{
    public static bool $handled    = false;
    public static string $usedQueue = '';

    public string $queue = 'emails';

    public function handle(): void
    {
        self::$handled   = true;
        self::$usedQueue = $this->queue;
    }
}

class DelayTestJob extends Job
{
    public static bool $handled        = false;
    public static int  $capturedDelay  = 0;

    public function handle(): void
    {
        self::$capturedDelay = $this->delay;
        self::$handled       = true;
    }
}
