<?php

namespace App\Exceptions;

use RuntimeException;

/** Исключение валидации входных данных.
 * Бросается в BaseRequest::validate() при наличии ошибок.
 */
class ValidationException extends RuntimeException
{
    /** @param array<string, string> $errors */
    public function __construct(private readonly array $errors)
    {
        parent::__construct('Validation failed.');
    }

    /** Вернуть массив ошибок валидации.
     * @return array<string, string>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
}
