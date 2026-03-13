<?php

namespace App\Console\Commands\Migrate;

use Throwable;
use RuntimeException;
use App\Abstracts\BaseCommand;
use App\Facades\DB;

/** Применяет все непримененные миграции из database/migrations/.
 * Создаёт таблицу migrations при первом запуске.
 * Каждый запуск — отдельный batch.
 */
class MigrateCommand extends BaseCommand
{
    use EnsuresMigrationsTable;

    public static string $name = 'migrate';

    private string $migrationsPath;

    /** @param string|null $migrationsPath Путь к директории миграций (для тестов). */
    public function __construct(?string $migrationsPath = null)
    {
        $this->migrationsPath = $migrationsPath ?? __DIR__ . '/../../../../database/migrations';
    }

    /** Вернуть краткое описание команды. */
    public function description(): string
    {
        return 'Применить все непримененные миграции.';
    }

    /** Выполнить команду.
     * @param array<int, string> $args Аргументы командной строки.
     */
    public function handle(array $args): void
    {
        $this->ensureMigrationsTable();

        $ran     = $this->getRanMigrations();
        $files   = $this->getMigrationFiles();
        $pending = array_filter($files, fn($f) => !in_array(basename($f, '.php'), $ran, true));

        if (empty($pending)) {
            echo 'Nothing to migrate.' . PHP_EOL;
            return;
        }

        $batch = $this->getNextBatch();

        foreach ($pending as $file) {
            $name = basename($file, '.php');
            try {
                DB::transaction(function () use ($file, $name, $batch) {
                    $migration = (static fn($f) => require $f)($file);
                    $migration->up();
                    qi("INSERT INTO {$this->migrationsTable} (migration, batch) VALUES (?, ?)", [$name, $batch]);
                });
                echo self::GREEN . 'Migrated: ' . self::RESET . $name . PHP_EOL;
            } catch (Throwable $e) {
                throw new RuntimeException("Error migrating {$name}: " . $e->getMessage());
            }
        }
    }

    /** Получить список уже применённых миграций.
     * @return array<int, string>
     */
    private function getRanMigrations(): array
    {
        return array_column(q("SELECT migration FROM {$this->migrationsTable} ORDER BY id"), 'migration');
    }

    /** Получить список файлов миграций, отсортированных по имени.
     * @return array<int, string>
     */
    private function getMigrationFiles(): array
    {
        $files = glob($this->migrationsPath . '/*.php') ?: [];
        sort($files);
        return $files;
    }

    /** Получить номер следующего batch.
     * @return int
     */
    private function getNextBatch(): int
    {
        $row = q1("SELECT MAX(batch) AS max_batch FROM {$this->migrationsTable}");
        return (int)($row['max_batch'] ?? 0) + 1;
    }
}
