<?php

namespace App\Providers;

use App\Core\EventDispatcher;
use App\Events\LoginFailed;
use App\Events\UserCreated;
use App\Events\UserDeleted;
use App\Events\UserUpdated;
use App\Events\LoginSucceeded;
use App\Listeners\LogAuthEvent;
use App\Listeners\LogUserChange;

/** Провайдер событий. Регистрирует слушателей в диспетчере при загрузке приложения. */
class EventServiceProvider
{
    /** Зарегистрировать слушателей событий в диспетчере.
     * @param EventDispatcher $dispatcher Экземпляр диспетчера событий.
     */
    public static function register(EventDispatcher $dispatcher): void
    {
        $dispatcher->listen(LoginSucceeded::class, LogAuthEvent::class);
        $dispatcher->listen(LoginFailed::class, LogAuthEvent::class);
        $dispatcher->listen(UserCreated::class, LogUserChange::class);
        $dispatcher->listen(UserUpdated::class, LogUserChange::class);
        $dispatcher->listen(UserDeleted::class, LogUserChange::class);
    }
}
