<?php

namespace App\Console\Commands;

use App\Abstracts\BaseCommand;
use App\Console\CommandInterface;

/** Выводит список всех доступных команд в стиле Laravel с цветами. */
class HelpCommand extends BaseCommand
{
    /** @var array<string, class-string<CommandInterface>> */
    private array $commands;

    /** @param array<string, class-string<CommandInterface>> $commands Карта команд из Kernel. */
    public function __construct(array $commands)
    {
        $this->commands = $commands;
    }

    /** Вернуть краткое описание команды. */
    public function description(): string
    {
        return 'Показать список всех доступных команд.';
    }

    /** Выполнить команду.
     * @param array<int, string> $args Аргументы командной строки.
     */
    public function handle(array $args): void
    {
        $all   = array_merge(['help' => self::class], $this->commands);
        $width = max(array_map('strlen', array_keys($all)));

        [$ungrouped, $groups] = $this->groupCommands($all);

        echo 'Tiny Framework'
            . ' ' . self::GREEN . $this->version() . self::RESET . PHP_EOL;
        echo PHP_EOL;
        echo self::YELLOW . self::BOLD . 'Usage:' . self::RESET . PHP_EOL;
        echo '  command [arguments]' . PHP_EOL;
        echo PHP_EOL;
        echo self::YELLOW . self::BOLD . 'Available commands:' . self::RESET . PHP_EOL;

        foreach ($ungrouped as $name => $class) {
            $desc = $name === 'help' ? $this->description() : (new $class())->description();
            $this->printLine($name, $desc, $width);
        }

        foreach ($groups as $group => $commands) {
            echo ' ' . self::YELLOW . $group . self::RESET . PHP_EOL;
            foreach ($commands as $name => $class) {
                $this->printLine($name, (new $class())->description(), $width);
            }
        }

        echo PHP_EOL;
    }

    /** Разделить команды на ungrouped (без префикса) и группы (по префиксу до ':').
     * @param array<string, class-string<CommandInterface>> $commands
     * @return array{array<string, class-string<CommandInterface>>, array<string, array<string, class-string<CommandInterface>>>}
     */
    private function groupCommands(array $commands): array
    {
        $ungrouped = [];
        $groups    = [];

        foreach ($commands as $name => $class) {
            if (!str_contains($name, ':')) {
                $ungrouped[$name] = $class;
            } else {
                $group                 = explode(':', $name)[0];
                $groups[$group][$name] = $class;
            }
        }

        return [$ungrouped, $groups];
    }

    /** Прочитать версию из composer.json.
     * @return string
     */
    private function version(): string
    {
        $composerPath = __DIR__ . '/../../../composer.json';

        if (!file_exists($composerPath)) {
            return '';
        }

        $data = json_decode(file_get_contents($composerPath), true);
        return 'v' . ($data['version'] ?? 'unknown');
    }

    /** Вывести одну строку команды.
     * @param string $name        Имя команды.
     * @param string $description Описание команды.
     * @param int    $width       Ширина колонки имени.
     */
    private function printLine(string $name, string $description, int $width): void
    {
        echo '  ' . self::GREEN . str_pad($name, $width) . self::RESET
            . '  ' . self::GRAY . $description . self::RESET . PHP_EOL;
    }
}
