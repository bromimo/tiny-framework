<?php

namespace Tests\Unit\Observers;

use App\Models\User;
use App\Facades\Event;
use App\Events\UserCreated;
use App\Events\UserDeleted;
use App\Events\UserUpdated;
use App\Observers\UserObserver;
use PHPUnit\Framework\TestCase;
use App\Core\EventDispatcher;

/** Unit-тесты UserObserver. */
class UserObserverTest extends TestCase
{
    private UserObserver $observer;

    protected function setUp(): void
    {
        Event::setInstance(new EventDispatcher());
        Event::flush();
        $this->observer = new UserObserver();
    }

    public function test_created_dispatches_user_created_event(): void
    {
        $dispatched = [];
        Event::listen(UserCreated::class, function (UserCreated $e) use (&$dispatched) {
            $dispatched[] = $e;
        });

        $user = new User(['id' => 42, 'email' => 'test@example.com']);
        $this->observer->created($user);

        $this->assertCount(1, $dispatched);
        $this->assertSame(42, $dispatched[0]->userId);
        $this->assertSame('test@example.com', $dispatched[0]->email);
    }

    public function test_updated_dispatches_user_updated_event(): void
    {
        $dispatched = [];
        Event::listen(UserUpdated::class, function (UserUpdated $e) use (&$dispatched) {
            $dispatched[] = $e;
        });

        $user = new User(['id' => 5]);
        $this->observer->updated($user, ['email', 'first_name']);

        $this->assertCount(1, $dispatched);
        $this->assertSame(5, $dispatched[0]->userId);
        $this->assertSame(['email', 'first_name'], $dispatched[0]->changedFields);
    }

    public function test_deleted_dispatches_user_deleted_event(): void
    {
        $dispatched = [];
        Event::listen(UserDeleted::class, function (UserDeleted $e) use (&$dispatched) {
            $dispatched[] = $e;
        });

        $this->observer->deleted(10);

        $this->assertCount(1, $dispatched);
        $this->assertSame(10, $dispatched[0]->userId);
    }
}
