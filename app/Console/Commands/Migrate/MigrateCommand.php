<?php

namespace App\Console\Commands\Migrate;

use Throwable;
use RuntimeException;
use App\Console\CommandInterface;

/** Применяет все непримененные миграции из database/migrations/.
 * Создаёт таблицу migrations при первом запуске.
 * Каждый запуск — отдельный batch.
 */
class MigrateCommand implements CommandInterface
{
    use EnsuresMigrationsTable;

    private string $migrationsPath;

    /** @param string|null $migrationsPath Путь к директории миграций (для тестов). */
    public function __construct(?string $migrationsPath = null)
    {
        $this->migrationsPath = $migrationsPath ?? __DIR__ . '/../../../../database/migrations';
    }

    /** Выполнить команду.
     * @param array<int, string> $args Аргументы командной строки (не используются).
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
                $migration = (static fn($f) => require $f)($file);
                $migration->up();
                qi('INSERT INTO migrations (migration, batch) VALUES (?, ?)', [$name, $batch]);
                echo "Migrated: {$name}" . PHP_EOL;
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
        return array_column(q('SELECT migration FROM migrations ORDER BY id'), 'migration');
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
        $row = q1('SELECT MAX(batch) AS max_batch FROM migrations');
        return (int)($row['max_batch'] ?? 0) + 1;
    }
}
