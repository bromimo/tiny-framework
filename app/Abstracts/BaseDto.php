<?php

namespace App\Abstracts;

abstract class BaseDto
{
    abstract public function toArray(): array;
}
