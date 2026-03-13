<?php

namespace App\Console\Commands\Db;

use RuntimeException;
use App\Abstracts\BaseCommand;
use Database\Seeders\DatabaseSeeder;

/** Запускает сидеры для наполнения базы тестовыми данными. */
class DbSeedCommand extends BaseCommand
{
    public static string $name = 'db:seed';

    /** Вернуть краткое описание команды. */
    public function description(): string
    {
        return 'Наполнить базу данных тестовыми данными: [--class=DatabaseSeeder]';
    }

    /** Выполнить команду.
     * @param array<int, string> $args Аргументы: [--class=<SeederClass>]
     */
    public function handle(array $args): void
    {
        $class = DatabaseSeeder::class;

        foreach ($args as $arg) {
            if (str_starts_with($arg, '--class=')) {
                $class = substr($arg, 8);
            }
        }

        if (!class_exists($class)) {
            throw new RuntimeException("Seeder class not found: {$class}");
        }

        echo self::GREEN . 'Seeding: ' . self::RESET . $class . PHP_EOL;

        (new $class())->run();

        echo self::GREEN . 'Database seeding completed successfully.' . self::RESET . PHP_EOL;
    }
}
