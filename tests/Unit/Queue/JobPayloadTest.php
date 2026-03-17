<?php

namespace Tests\Unit\Queue;

use App\Abstracts\Job;
use App\Queue\JobPayload;
use PHPUnit\Framework\TestCase;

class JobPayloadTest extends TestCase
{
    public function test_job_payload_stores_values(): void
    {
        $job = new class extends Job {
            public function handle(): void {}
        };

        $payload = new JobPayload(
            id: 1,
            queue: 'default',
            job: $job,
            attempts: 2,
        );

        $this->assertSame(1, $payload->id);
        $this->assertSame('default', $payload->queue);
        $this->assertSame($job, $payload->job);
        $this->assertSame(2, $payload->attempts);
    }
}
