<?php

namespace Database\Seeders;

/** Базовый класс для сидеров. Предоставляет метод вызова дочерних сидеров. */
abstract class BaseSeeder
{
    /** Выполнить сидер. */
    abstract public function run(): void;

    /** Запустить один или несколько сидеров.
     * @param class-string|list<class-string> $seeders Класс или массив классов сидеров.
     */
    protected function call(string|array $seeders): void
    {
        foreach ((array) $seeders as $class) {
            (new $class())->run();
        }
    }
}
