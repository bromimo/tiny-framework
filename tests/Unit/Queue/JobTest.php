<?php

namespace Tests\Unit\Queue;

use App\Abstracts\Job;
use PHPUnit\Framework\TestCase;

class JobTest extends TestCase
{
    public function test_job_has_default_properties(): void
    {
        $job = new class extends Job {
            public function handle(): void {}
        };

        $this->assertSame(0, $job->timeout);
        $this->assertSame(0, $job->tries);
        $this->assertSame([], $job->backoff);
        $this->assertSame('', $job->queue);
        $this->assertSame(0, $job->delay);
    }

    public function test_job_properties_can_be_overridden(): void
    {
        $job = new class extends Job {
            public int $timeout = 120;
            public int $tries = 5;
            public array $backoff = [10, 60];
            public string $queue = 'emails';
            public int $delay = 30;

            public function handle(): void {}
        };

        $this->assertSame(120, $job->timeout);
        $this->assertSame(5, $job->tries);
        $this->assertSame([10, 60], $job->backoff);
        $this->assertSame('emails', $job->queue);
        $this->assertSame(30, $job->delay);
    }

    public function test_job_is_serializable(): void
    {
        $job = new ConcreteTestJob(42);
        $restored = unserialize(serialize($job));

        $this->assertInstanceOf(ConcreteTestJob::class, $restored);
        $this->assertSame(42, $restored->userId);
    }

    public function test_failed_hook_is_callable(): void
    {
        $job = new class extends Job {
            public ?string $failReason = null;
            public function handle(): void {}
            public function failed(\Throwable $e): void
            {
                $this->failReason = $e->getMessage();
            }
        };

        $job->failed(new \RuntimeException('test error'));
        $this->assertSame('test error', $job->failReason);
    }
}

class ConcreteTestJob extends Job
{
    public function __construct(public readonly int $userId) {}
    public function handle(): void {}
}
