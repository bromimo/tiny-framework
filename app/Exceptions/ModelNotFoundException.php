<?php

namespace App\Exceptions;

use RuntimeException;

/** Исключение при ненайденной модели в ControllerResolver. */
class ModelNotFoundException extends RuntimeException
{
    public function __construct(string $message = 'Resource not found.')
    {
        parent::__construct($message);
    }
}
