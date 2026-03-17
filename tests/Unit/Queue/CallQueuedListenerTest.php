<?php

namespace Tests\Unit\Queue;

use App\Abstracts\Job;
use App\Facades\App;
use App\Core\Container;
use App\Queue\CallQueuedListener;
use PHPUnit\Framework\TestCase;

class CallQueuedListenerTest extends TestCase
{
    protected function setUp(): void
    {
        App::setInstance(new Container());
    }

    protected function tearDown(): void
    {
        App::reset();
    }

    public function test_extends_job(): void
    {
        $listener = new CallQueuedListener(StubQueuedListener::class, new StubQueuedEvent());
        $this->assertInstanceOf(Job::class, $listener);
    }

    public function test_handle_invokes_listener_with_event(): void
    {
        $event = new StubQueuedEvent();
        $listener = new CallQueuedListener(StubQueuedListener::class, $event);

        $listener->handle();

        $this->assertTrue($event->handled);
    }

    public function test_copies_properties_from_listener(): void
    {
        $event = new StubQueuedEvent();
        $listener = new CallQueuedListener(StubQueuedListenerWithConfig::class, $event);

        $this->assertSame(120, $listener->timeout);
        $this->assertSame(5, $listener->tries);
        $this->assertSame([10, 60], $listener->backoff);
        $this->assertSame('emails', $listener->queue);
    }

    public function test_is_serializable(): void
    {
        $event = new StubQueuedEvent();
        $listener = new CallQueuedListener(StubQueuedListener::class, $event);

        $restored = unserialize(serialize($listener));

        $this->assertInstanceOf(CallQueuedListener::class, $restored);
    }
}

class StubQueuedEvent
{
    public bool $handled = false;
}

class StubQueuedListener
{
    public function __invoke(StubQueuedEvent $event): void
    {
        $event->handled = true;
    }
}

class StubQueuedListenerWithConfig
{
    public int $timeout = 120;
    public int $tries = 5;
    public array $backoff = [10, 60];
    public string $queue = 'emails';

    public function __invoke(StubQueuedEvent $event): void
    {
        $event->handled = true;
    }
}
