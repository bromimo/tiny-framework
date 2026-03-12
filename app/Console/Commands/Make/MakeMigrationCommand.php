<?php

namespace App\Console\Commands\Make;

use DateTimeZone;
use RuntimeException;
use DateTimeImmutable;
use App\Console\CommandInterface;

/** Генерирует файл-заготовку новой миграции на основе стаба.
 * Использует конфиг database.migrations.stub или дефолтный путь.
 */
class MakeMigrationCommand implements CommandInterface
{
    private string $migrationsPath;
    private string $stubsPath;

    /** @param string|null $migrationsPath Путь к директории миграций (для тестов).
     * @param string|null $stubsPath Путь к директории стабов (для тестов).
     */
    public function __construct(?string $migrationsPath = null, ?string $stubsPath = null)
    {
        $this->migrationsPath = $migrationsPath ?? __DIR__ . '/../../../../database/migrations';
        $this->stubsPath      = $stubsPath ?? __DIR__ . '/../../../../stubs';
    }

    /** Выполнить команду.
     * @param array<int, string> $args Аргументы: <name> [--stub=<path>]
     */
    public function handle(array $args): void
    {
        $name         = null;
        $stubOverride = null;

        foreach ($args as $arg) {
            if (str_starts_with($arg, '--stub=')) {
                $stubOverride = substr($arg, 7);
            } else {
                $name = $arg;
            }
        }

        if ($name === null) {
            echo 'Usage: php run make:migration <name> [--stub=<path>]' . PHP_EOL;
            throw new RuntimeException('Migration name is required.');
        }

        $stubPath = $this->resolveStub($stubOverride);

        if (!file_exists($stubPath)) {
            throw new RuntimeException("Stub file not found: {$stubPath}");
        }

        $content   = file_get_contents($stubPath);
        $timestamp = (new DateTimeImmutable('now', new DateTimeZone('UTC')))->format('Y_m_d_His');
        $filename  = "{$timestamp}_{$name}.php";
        $filepath  = $this->migrationsPath . '/' . $filename;

        file_put_contents($filepath, $content);
        echo "Created: database/migrations/{$filename}" . PHP_EOL;
    }

    /** Определить путь к стабу по приоритету: флаг → конфиг → дефолт.
     * @param string|null $override Значение флага --stub.
     * @return string Абсолютный путь к файлу стаба.
     */
    private function resolveStub(?string $override): string
    {
        if ($override !== null) {
            return $override;
        }

        $configured = config('database.migrations.stub', null);
        if ($configured !== null) {
            return $this->stubsPath . '/' . $configured;
        }

        return $this->stubsPath . '/migrations/migration.stub';
    }
}
