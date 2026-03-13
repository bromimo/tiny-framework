<?php

namespace App\Exceptions;

use Throwable;
use RuntimeException;

/** Исключение при выполнении SQL-запроса. Оборачивает PDOException. */
class QueryException extends RuntimeException
{
    /** @param string         $message  Сообщение об ошибке.
     * @param string         $sqlState SQLSTATE-код ошибки (например, '23000').
     * @param Throwable|null $previous Исходное исключение.
     */
    public function __construct(string $message, private readonly string $sqlState, ?Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
    }

    /** Вернуть SQLSTATE-код ошибки.
     * @return string
     */
    public function getSqlState(): string
    {
        return $this->sqlState;
    }
}
