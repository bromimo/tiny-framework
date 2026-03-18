<?php

namespace App\Exceptions;

use RuntimeException;

/** Исключение аутентификации (401). */
class AuthenticationException extends RuntimeException
{
    /** Создать исключение аутентификации.
     * @param string $message
     */
    public function __construct(string $message = 'Unauthorized.')
    {
        parent::__construct($message);
    }
}
