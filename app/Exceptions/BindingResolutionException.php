<?php

namespace App\Exceptions;

use RuntimeException;

/** Исключение при невозможности зарезолвить зависимость из контейнера. */
class BindingResolutionException extends RuntimeException
{
}
