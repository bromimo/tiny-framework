<?php

namespace App\Listeners;

use App\Core\Logger;
use App\Events\UserCreated;
use App\Events\UserDeleted;
use App\Events\UserUpdated;

/** Логирует события изменения пользователей. */
class LogUserChange
{
    /** Обработать событие создания, обновления или удаления пользователя.
     * @param UserCreated|UserUpdated|UserDeleted $event Событие изменения пользователя.
     * @return void
     */
    public function __invoke(UserCreated|UserUpdated|UserDeleted $event): void
    {
        if ($event instanceof UserCreated) {
            Logger::info("User created: {$event->userId} ({$event->email})");
        } elseif ($event instanceof UserUpdated) {
            $fields = implode(', ', $event->changedFields);
            Logger::info("User updated: {$event->userId}, fields: {$fields}");
        } else {
            Logger::info("User deleted: {$event->userId}");
        }
    }
}
