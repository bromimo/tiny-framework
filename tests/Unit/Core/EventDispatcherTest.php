<?php

namespace Tests\Unit\Core;

use App\Core\EventDispatcher;
use PHPUnit\Framework\TestCase;
use App\Contracts\ShouldQueue;

class EventDispatcherTest extends TestCase
{
    private EventDispatcher $dispatcher;

    protected function setUp(): void
    {
        $this->dispatcher = new EventDispatcher();
    }

    public function test_dispatch_calls_registered_callable_listener(): void
    {
        $called = false;
        $this->dispatcher->listen(\stdClass::class, function (\stdClass $event) use (&$called) {
            $called = true;
        });

        $this->dispatcher->dispatch(new \stdClass());
        $this->assertTrue($called);
    }

    public function test_dispatch_calls_class_string_listener_via_invoke(): void
    {
        $this->dispatcher->listen(StubEvent::class, StubListener::class);

        $event = new StubEvent();
        $this->dispatcher->dispatch($event);

        $this->assertTrue($event->handled);
    }

    public function test_dispatch_calls_multiple_listeners_in_registration_order(): void
    {
        $order = [];

        $this->dispatcher->listen(\stdClass::class, function () use (&$order) {
            $order[] = 'first';
        });
        $this->dispatcher->listen(\stdClass::class, function () use (&$order) {
            $order[] = 'second';
        });
        $this->dispatcher->listen(\stdClass::class, function () use (&$order) {
            $order[] = 'third';
        });

        $this->dispatcher->dispatch(new \stdClass());
        $this->assertSame(['first', 'second', 'third'], $order);
    }

    public function test_dispatch_without_listeners_returns_event(): void
    {
        $event = new \stdClass();
        $result = $this->dispatcher->dispatch($event);
        $this->assertSame($event, $result);
    }

    public function test_has_listeners_returns_true_when_registered(): void
    {
        $this->dispatcher->listen(\stdClass::class, function () {});
        $this->assertTrue($this->dispatcher->hasListeners(\stdClass::class));
    }

    public function test_has_listeners_returns_false_when_empty(): void
    {
        $this->assertFalse($this->dispatcher->hasListeners(\stdClass::class));
    }

    public function test_flush_clears_all_listeners(): void
    {
        $this->dispatcher->listen(\stdClass::class, function () {});
        $this->dispatcher->listen(StubEvent::class, function () {});

        $this->dispatcher->flush();

        $this->assertFalse($this->dispatcher->hasListeners(\stdClass::class));
        $this->assertFalse($this->dispatcher->hasListeners(StubEvent::class));
    }

    public function test_flush_clears_specific_event_only(): void
    {
        $this->dispatcher->listen(\stdClass::class, function () {});
        $this->dispatcher->listen(StubEvent::class, function () {});

        $this->dispatcher->flush(\stdClass::class);

        $this->assertFalse($this->dispatcher->hasListeners(\stdClass::class));
        $this->assertTrue($this->dispatcher->hasListeners(StubEvent::class));
    }

    public function test_dispatch_queues_should_queue_listener_instead_of_invoking(): void
    {
        \App\Facades\Queue::setInstance(new \App\Queue\QueueManager('sync'));

        $this->dispatcher->listen(StubEvent::class, StubQueueableListener::class);
        StubQueueableListener::$invoked = false;

        $event = new StubEvent();
        $this->dispatcher->dispatch($event);

        // Через SyncDriver — listener выполняется сразу через CallQueuedListener
        $this->assertTrue(StubQueueableListener::$invoked);

        \App\Facades\Queue::reset();
    }
}

class StubEvent
{
    public bool $handled = false;
}

class StubListener
{
    public function __invoke(StubEvent $event): void
    {
        $event->handled = true;
    }
}

class StubQueueableListener implements \App\Contracts\ShouldQueue
{
    public static bool $invoked = false;

    public function __invoke(StubEvent $event): void
    {
        self::$invoked = true;
    }
}
