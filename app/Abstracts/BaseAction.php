<?php

namespace App\Abstracts;

/** Базовый класс для Actions — классов с единственной ответственностью. */
abstract class BaseAction
{
    /** Выполнить действие. */
    abstract public function run(mixed ...$args);
}
