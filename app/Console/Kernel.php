<?php

namespace App\Console;

use RuntimeException;
use App\Console\Commands\Make\MakeMigrationCommand;
use App\Console\Commands\Migrate\MigrateCommand;
use App\Console\Commands\Migrate\MigrateRollbackCommand;

/** Диспатчер консольных команд.
 * Резолвит имя команды из аргументов и вызывает соответствующий handler.
 */
class Kernel
{
    /** @var array<string, class-string> */
    private array $commands = [
        'migrate'          => MigrateCommand::class,
        'migrate:rollback' => MigrateRollbackCommand::class,
        'make:migration'   => MakeMigrationCommand::class,
    ];

    /** Запустить команду на основе аргументов CLI.
     * @param array<int, string> $argv Массив аргументов командной строки.
     */
    public function handle(array $argv): void
    {
        $command = $argv[1] ?? null;
        $args    = array_slice($argv, 2);

        if ($command === null || !isset($this->commands[$command])) {
            $available = implode(', ', array_keys($this->commands));
            echo "Unknown command. Available: {$available}" . PHP_EOL;
            throw new RuntimeException('Unknown command: ' . ($command ?? '(none)'));
        }

        $handler = new $this->commands[$command]();
        $handler->handle($args);
    }
}
