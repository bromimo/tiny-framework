<?php

namespace Tests\Feature\Traits;

use App\DTOs\UserDto;
use App\Models\User;
use App\Facades\Event;
use App\Events\UserCreated;
use App\Events\UserDeleted;
use App\Events\UserUpdated;
use Tests\Support\FeatureTestCase;

/** Интеграционные тесты trait HasObserver через модель User. */
class HasObserverTest extends FeatureTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Event::flush();
    }

    protected function tearDown(): void
    {
        User::removeObserver();
        parent::tearDown();
    }

    private function makeDto(string $email = 'observer@example.com'): UserDto
    {
        return new UserDto(
            first_name: 'Test',
            last_name: 'User',
            email: $email,
            password: 'secret123',
        );
    }

    public function test_observer_created_hook_is_called(): void
    {
        $dispatched = [];
        Event::listen(UserCreated::class, function (UserCreated $e) use (&$dispatched) {
            $dispatched[] = $e;
        });

        $user = User::create($this->makeDto());

        $this->assertCount(1, $dispatched);
        $this->assertSame($user->id, $dispatched[0]->userId);
    }

    public function test_observer_updated_hook_receives_changed_fields(): void
    {
        $dispatched = [];
        Event::listen(UserUpdated::class, function (UserUpdated $e) use (&$dispatched) {
            $dispatched[] = $e;
        });

        $user = User::create($this->makeDto());

        // flush UserCreated events
        $dispatched = [];

        User::update($user->id, new UserDto(
            first_name: 'Changed',
            last_name: '',
            email: '',
            password: null,
        ));

        $this->assertCount(1, $dispatched);
        $this->assertContains('first_name', $dispatched[0]->changedFields);
    }

    public function test_observer_deleted_hook_is_called(): void
    {
        $dispatched = [];
        Event::listen(UserDeleted::class, function (UserDeleted $e) use (&$dispatched) {
            $dispatched[] = $e;
        });

        $user = User::create($this->makeDto());
        User::deleteById($user->id);

        $this->assertCount(1, $dispatched);
        $this->assertSame($user->id, $dispatched[0]->userId);
    }

    public function test_observer_creating_returns_false_cancels_insert(): void
    {
        User::observe(CancellingObserver::class);

        $result = User::create($this->makeDto());

        $this->assertNull($result);
    }

    public function test_observer_deleting_returns_false_cancels_delete(): void
    {
        $user = User::create($this->makeDto());

        User::observe(CancellingObserver::class);

        $result = User::deleteById($user->id);

        $this->assertFalse($result);
        $this->assertNotNull(User::findById($user->id));
    }

    public function test_model_without_observer_works_normally(): void
    {
        User::removeObserver();

        $user = User::create($this->makeDto());

        $this->assertInstanceOf(User::class, $user);
        $this->assertSame('observer@example.com', $user->email);
    }

    public function test_manual_observe_overrides_attribute(): void
    {
        User::observe(NoopObserver::class);

        $dispatched = [];
        Event::listen(UserCreated::class, function () use (&$dispatched) {
            $dispatched[] = true;
        });

        User::create($this->makeDto());

        // NoopObserver не диспатчит события — dispatched должен быть пуст
        $this->assertEmpty($dispatched);
    }

    public function test_observer_auto_registers_from_attribute(): void
    {
        // removeObserver() сбрасывает кеш, при следующем CRUD resolveObserver перечитает атрибут
        User::removeObserver();

        $dispatched = [];
        Event::listen(UserCreated::class, function (UserCreated $e) use (&$dispatched) {
            $dispatched[] = $e;
        });

        User::create($this->makeDto());

        $this->assertCount(1, $dispatched);
    }
}

/** Обсервер, отменяющий creating и deleting. */
class CancellingObserver
{
    public function creating(array &$data): false
    {
        return false;
    }

    public function deleting(int $id): false
    {
        return false;
    }
}

/** Пустой обсервер без хуков — для проверки ручной перезаписи. */
class NoopObserver
{
}
