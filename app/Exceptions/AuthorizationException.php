<?php

namespace App\Exceptions;

use RuntimeException;

/** Исключение авторизации (403). */
class AuthorizationException extends RuntimeException
{
    private string $ability;

    /** Создать исключение авторизации.
     * @param string $message
     * @param string $ability Действие, на которое не хватило прав.
     */
    public function __construct(string $message = 'Forbidden.', string $ability = '')
    {
        parent::__construct($message);
        $this->ability = $ability;
    }

    /** Получить имя действия.
     * @return string
     */
    public function getAbility(): string
    {
        return $this->ability;
    }
}
