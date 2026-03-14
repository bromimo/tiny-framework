<?php

namespace App\Events;

/** Событие успешной аутентификации. */
readonly class LoginSucceeded
{
    /** @param int    $userId ID аутентифицированного пользователя.
     * @param string $ip     IP-адрес клиента.
     */
    public function __construct(
        public int $userId,
        public string $ip,
    ) {}
}
