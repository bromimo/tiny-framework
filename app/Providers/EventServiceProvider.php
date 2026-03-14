<?php

namespace App\Providers;

use App\Core\EventDispatcher;

/** Провайдер событий. Регистрирует слушателей в диспетчере при загрузке приложения. */
class EventServiceProvider
{
    /** Зарегистрировать слушателей событий в диспетчере.
     * @param EventDispatcher $dispatcher Экземпляр диспетчера событий.
     */
    public static function register(EventDispatcher $dispatcher): void
    {
        // Will be filled after events and listeners exist
    }
}
