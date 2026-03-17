<?php

namespace Tests\Feature\Queue;

use App\Facades\DB;
use App\Facades\Env;
use App\Abstracts\Job;
use App\Facades\Cache;
use App\Facades\Queue;
use App\Facades\Config;
use App\Queue\QueueManager;
use PHPUnit\Framework\TestCase;

/**
 * Интеграционный тест очередей с реальной БД.
 * НЕ наследует FeatureTestCase чтобы избежать конфликта вложенных транзакций
 * (DatabaseDriver::pop() использует DB::transaction() внутри).
 */
class QueueIntegrationTest extends TestCase
{
    protected function setUp(): void
    {
        Env::safeLoad(__DIR__ . '/../../..', '.env.testing');
        Cache::init();
        Config::load(__DIR__ . '/../../../config');

        Queue::setInstance(new QueueManager('database'));
        DB::query_insert('DELETE FROM jobs');
        DB::query_insert('DELETE FROM failed_jobs');
    }

    protected function tearDown(): void
    {
        DB::query_insert('DELETE FROM jobs');
        DB::query_insert('DELETE FROM failed_jobs');
        Queue::reset();
        DB::reset();
    }

    public function test_push_inserts_job_into_database(): void
    {
        $job = new DummyJob(42);
        Queue::push($job);

        $row = DB::query_once('SELECT * FROM jobs WHERE queue = ?', ['default']);
        $this->assertNotNull($row);
        $this->assertSame('default', $row['queue']);
        $this->assertSame(0, (int) $row['attempts']);
        $this->assertNull($row['reserved_at']);
    }

    public function test_pop_claims_and_returns_job(): void
    {
        $job = new DummyJob(99);
        Queue::push($job);

        $payload = Queue::pop('default', 'test:1');

        $this->assertNotNull($payload);
        $this->assertInstanceOf(DummyJob::class, $payload->job);
        $this->assertSame(99, $payload->job->userId);
        $this->assertSame(1, $payload->attempts);
    }

    public function test_pop_returns_null_on_empty_queue(): void
    {
        $payload = Queue::pop('default', 'test:1');
        $this->assertNull($payload);
    }

    public function test_delete_removes_job(): void
    {
        Queue::push(new DummyJob(1));
        $payload = Queue::pop('default', 'test:1');

        Queue::delete($payload->id);

        $row = DB::query_once('SELECT * FROM jobs WHERE id = ?', [$payload->id]);
        $this->assertNull($row);
    }

    public function test_release_returns_job_to_queue(): void
    {
        Queue::push(new DummyJob(1));
        $payload = Queue::pop('default', 'test:1');

        Queue::release($payload->id, 0);

        $row = DB::query_once('SELECT * FROM jobs WHERE id = ?', [$payload->id]);
        $this->assertNotNull($row);
        $this->assertNull($row['reserved_at']);
        $this->assertNull($row['worker_id']);
    }

    public function test_fail_moves_job_to_failed_jobs(): void
    {
        Queue::push(new DummyJob(1));
        $payload = Queue::pop('default', 'test:1');

        Queue::fail($payload->id, new \RuntimeException('test error'));

        $job = DB::query_once('SELECT * FROM jobs WHERE id = ?', [$payload->id]);
        $this->assertNull($job);

        $failed = DB::query_once('SELECT * FROM failed_jobs ORDER BY id DESC LIMIT 1');
        $this->assertNotNull($failed);
        $this->assertSame('default', $failed['queue']);
        $this->assertStringContainsString('test error', $failed['exception']);
    }

    public function test_size_counts_pending_jobs(): void
    {
        $this->assertSame(0, Queue::size('default'));

        Queue::push(new DummyJob(1));
        Queue::push(new DummyJob(2));

        $this->assertSame(2, Queue::size('default'));
    }

    public function test_delayed_job_not_available_immediately(): void
    {
        $job = new DummyJob(1);
        Queue::later(3600, $job);

        $payload = Queue::pop('default', 'test:1');
        $this->assertNull($payload); // Ещё не available
    }

    public function test_named_queue(): void
    {
        $job = new DummyJob(1);
        $job->queue = 'emails';
        Queue::push($job);

        $this->assertSame(0, Queue::size('default'));
        $this->assertSame(1, Queue::size('emails'));
    }
}

class DummyJob extends Job
{
    public function __construct(public readonly int $userId) {}

    public function handle(): void
    {
        // No-op для тестов
    }
}
