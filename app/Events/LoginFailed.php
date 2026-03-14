<?php

namespace App\Events;

/** Событие неудачной попытки аутентификации. */
readonly class LoginFailed
{
    /** @param string $email Email, с которым выполнялась попытка входа.
     * @param string $ip    IP-адрес клиента.
     */
    public function __construct(
        public string $email,
        public string $ip,
    ) {}
}
