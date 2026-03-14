<?php

namespace App\Listeners;

use App\Core\Logger;
use App\Events\LoginFailed;
use App\Events\LoginSucceeded;

/** Логирует события аутентификации. */
class LogAuthEvent
{
    /** Обработать событие успешного или неудачного входа.
     * @param LoginSucceeded|LoginFailed $event Событие аутентификации.
     * @return void
     */
    public function __invoke(LoginSucceeded|LoginFailed $event): void
    {
        if ($event instanceof LoginSucceeded) {
            Logger::info("Login succeeded for user {$event->userId} from {$event->ip}");
        } else {
            Logger::info("Login failed for email {$event->email} from {$event->ip}");
        }
    }
}
