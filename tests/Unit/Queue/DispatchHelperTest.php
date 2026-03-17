<?php

namespace Tests\Unit\Queue;

use App\Abstracts\Job;
use App\Facades\Queue;
use PHPUnit\Framework\TestCase;

class DispatchHelperTest extends TestCase
{
    protected function setUp(): void
    {
        Queue::reset();
    }

    protected function tearDown(): void
    {
        Queue::reset();
    }

    public function test_dispatch_without_delay_pushes_immediately(): void
    {
        $job = new DispatchNoDelayJob();

        dispatch($job);

        $this->assertTrue(DispatchNoDelayJob::$handled);
        $this->assertSame(0, DispatchNoDelayJob::$capturedDelay);
    }

    public function test_dispatch_with_delay_uses_later(): void
    {
        $job = new DispatchWithDelayJob();

        dispatch($job, 45);

        $this->assertTrue(DispatchWithDelayJob::$handled);
        $this->assertSame(45, DispatchWithDelayJob::$capturedDelay);
    }

    public function test_dispatch_zero_delay_calls_push_not_later(): void
    {
        $job = new DispatchZeroDelayJob();

        dispatch($job, 0);

        $this->assertTrue(DispatchZeroDelayJob::$handled);
        $this->assertSame(0, DispatchZeroDelayJob::$capturedDelay);
    }
}

class DispatchNoDelayJob extends Job
{
    public static bool $handled       = false;
    public static int  $capturedDelay = 0;

    public function handle(): void
    {
        self::$capturedDelay = $this->delay;
        self::$handled       = true;
    }
}

class DispatchWithDelayJob extends Job
{
    public static bool $handled       = false;
    public static int  $capturedDelay = 0;

    public function handle(): void
    {
        self::$capturedDelay = $this->delay;
        self::$handled       = true;
    }
}

class DispatchZeroDelayJob extends Job
{
    public static bool $handled       = false;
    public static int  $capturedDelay = 0;

    public function handle(): void
    {
        self::$capturedDelay = $this->delay;
        self::$handled       = true;
    }
}
