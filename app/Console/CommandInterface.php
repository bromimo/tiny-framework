<?php

namespace App\Console;

/** Контракт для консольных команд. */
interface CommandInterface
{
    /** Выполнить команду.
     * @param array<int, string> $args Аргументы командной строки.
     */
    public function handle(array $args): void;
}
