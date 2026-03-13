<?php

namespace App\Abstracts;

use RuntimeException;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;
use App\Console\CommandInterface;
use App\Console\Commands\HelpCommand;
use App\Console\Scheduling\Schedule;

/** Базовое консольное ядро. Обеспечивает диспатч команд и планировщик задач. */
abstract class BaseKernel
{
    /** @var array<string, class-string<CommandInterface>> Зарегистрированные команды. */
    protected array $registeredCommands = [];

    /** Зарегистрировать команды приложения. */
    abstract protected function commands(): void;

    /** Определить расписание периодических задач.
     * @param Schedule $schedule
     */
    abstract protected function schedule(Schedule $schedule): void;

    /** Запустить команду на основе аргументов CLI.
     * @param array<int, string> $argv Массив аргументов командной строки.
     */
    public function handle(array $argv): void
    {
        $this->commands();

        $command = $argv[1] ?? null;
        $args    = array_slice($argv, 2);

        if ($command === null || $command === 'help') {
            (new HelpCommand($this->registeredCommands))->handle($args);
            return;
        }

        if (!isset($this->registeredCommands[$command])) {
            echo "Unknown command \033[31m{$command}\033[0m. Run \033[32mphp run\033[0m to see available commands." . PHP_EOL;
            throw new RuntimeException('Unknown command: ' . $command);
        }

        $handler = new $this->registeredCommands[$command]();

        try {
            $handler->handle($args);
        } catch (RuntimeException $e) {
            echo $e->getMessage() . PHP_EOL;
            exit(1);
        }
    }

    /** Получить объект расписания с зарегистрированными задачами.
     * @return Schedule
     */
    public function getSchedule(): Schedule
    {
        $schedule = new Schedule();
        $this->schedule($schedule);
        return $schedule;
    }

    /** Зарегистрировать команду вручную.
     * @param string $name Имя команды.
     * @param class-string<CommandInterface> $class Класс команды.
     */
    protected function command(string $name, string $class): void
    {
        $this->registeredCommands[$name] = $class;
    }

    /** Авто-обнаружить команды в директории по статическому свойству $name.
     * Рекурсивно сканирует все PHP-файлы и регистрирует классы,
     * реализующие CommandInterface и имеющие непустой $name.
     * @param string $path Абсолютный путь к директории с командами.
     */
    protected function load(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }

        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path));

        foreach ($iterator as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            require_once $file->getRealPath();
        }

        foreach (get_declared_classes() as $class) {
            if (!is_subclass_of($class, CommandInterface::class)) {
                continue;
            }

            if (!isset($class::$name) || $class::$name === '') {
                continue;
            }

            $this->registeredCommands[$class::$name] = $class;
        }
    }
}
