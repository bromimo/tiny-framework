<?php

namespace App\Events;

/** Событие удаления пользователя. */
readonly class UserDeleted
{
    /** @param int $userId ID удалённого пользователя. */
    public function __construct(
        public int $userId,
    ) {}
}
