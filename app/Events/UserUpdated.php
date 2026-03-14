<?php

namespace App\Events;

/** Событие обновления пользователя. */
readonly class UserUpdated
{
    /** @param int   $userId        ID обновлённого пользователя.
     * @param array $changedFields Список изменённых полей.
     */
    public function __construct(
        public int $userId,
        public array $changedFields,
    ) {}
}
