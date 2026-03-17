<?php

namespace Tests\Unit\Queue\Drivers;

use App\Abstracts\Job;
use App\Contracts\QueueDriver;
use App\Queue\Drivers\SyncDriver;
use PHPUnit\Framework\TestCase;

class SyncDriverTest extends TestCase
{
    private SyncDriver $driver;

    protected function setUp(): void
    {
        $this->driver = new SyncDriver();
    }

    public function test_implements_queue_driver(): void
    {
        $this->assertInstanceOf(QueueDriver::class, $this->driver);
    }

    public function test_push_executes_job_immediately(): void
    {
        $job = new SyncHandledJob();

        $this->driver->push($job, 'default');
        $this->assertTrue(SyncHandledJob::$handled);
    }

    public function test_push_throws_on_job_failure(): void
    {
        $job = new SyncFailingJob();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('job failed');
        $this->driver->push($job, 'default');
    }

    public function test_pop_returns_null(): void
    {
        $this->assertNull($this->driver->pop('default', 'test:1'));
    }

    public function test_size_returns_zero(): void
    {
        $this->assertSame(0, $this->driver->size('default'));
    }
}

class SyncHandledJob extends Job
{
    public static bool $handled = false;

    public function handle(): void
    {
        self::$handled = true;
    }
}

class SyncFailingJob extends Job
{
    public function handle(): void
    {
        throw new \RuntimeException('job failed');
    }
}
