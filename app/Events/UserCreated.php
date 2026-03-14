<?php

namespace App\Events;

/** Событие создания пользователя. */
readonly class UserCreated
{
    /** @param int    $userId ID созданного пользователя.
     * @param string $email  Email созданного пользователя.
     */
    public function __construct(
        public int $userId,
        public string $email,
    ) {}
}
