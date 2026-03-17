<?php

namespace Tests\Unit\Queue;

use App\Abstracts\Job;
use App\Facades\Queue;
use App\Queue\QueueManager;
use App\Queue\Drivers\SyncDriver;
use PHPUnit\Framework\TestCase;

class QueueFacadeTest extends TestCase
{
    protected function setUp(): void
    {
        Queue::reset();
    }

    protected function tearDown(): void
    {
        Queue::reset();
    }

    public function test_push_executes_job_immediately_via_facade(): void
    {
        $job = new FacadeSyncJob();

        Queue::push($job);

        $this->assertTrue(FacadeSyncJob::$handled);
    }

    public function test_later_sets_delay_and_executes(): void
    {
        $job = new FacadeDelayJob();

        Queue::later(60, $job);

        $this->assertSame(60, FacadeDelayJob::$capturedDelay);
        $this->assertTrue(FacadeDelayJob::$handled);
    }

    public function test_auto_initialises_with_sync_driver(): void
    {
        $this->assertInstanceOf(SyncDriver::class, Queue::getDriver());
    }

    public function test_set_instance_overrides_default(): void
    {
        $manager = new QueueManager('sync');
        Queue::setInstance($manager);

        // Verify the manager was set: push executes via the injected manager
        $job = new FacadeSyncJob();
        Queue::push($job);
        $this->assertTrue(FacadeSyncJob::$handled);
        $this->assertInstanceOf(SyncDriver::class, Queue::getDriver());
    }

    public function test_reset_creates_fresh_instance(): void
    {
        $manager = new QueueManager('sync');
        Queue::setInstance($manager);
        Queue::reset();

        // After reset, a new sync manager is created lazily
        $this->assertInstanceOf(SyncDriver::class, Queue::getDriver());
    }

    public function test_pop_returns_null_for_sync_driver(): void
    {
        $this->assertNull(Queue::pop('default', 'worker:1'));
    }

    public function test_size_returns_zero_for_sync_driver(): void
    {
        $this->assertSame(0, Queue::size('default'));
    }
}

class FacadeSyncJob extends Job
{
    public static bool $handled = false;

    public function handle(): void
    {
        self::$handled = true;
    }
}

class FacadeDelayJob extends Job
{
    public static bool $handled       = false;
    public static int  $capturedDelay = 0;

    public function handle(): void
    {
        self::$capturedDelay = $this->delay;
        self::$handled       = true;
    }
}
