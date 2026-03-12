<?php

namespace App\Console\Commands\Migrate;

use Throwable;

/** Откатывает все миграции последнего batch в обратном порядке. */
class MigrateRollbackCommand
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
        $batch = $this->getLastBatch();

        if ($batch === null) {
            echo 'Nothing to rollback.' . PHP_EOL;
            return;
        }

        $migrations = q('SELECT * FROM migrations WHERE batch = ? ORDER BY id DESC', [$batch]);

        foreach ($migrations as $row) {
            $name = $row['migration'];
            $file = $this->migrationsPath . '/' . $name . '.php';

            if (!file_exists($file)) {
                echo "Migration file not found: {$name}.php" . PHP_EOL;
                exit(1);
            }

            try {
                $migration = (static fn($f) => require $f)($file);
                $migration->down();
                qi('DELETE FROM migrations WHERE id = ?', [$row['id']]);
                echo "Rolled back: {$name}" . PHP_EOL;
            } catch (Throwable $e) {
                echo "Error rolling back {$name}: " . $e->getMessage() . PHP_EOL;
                exit(1);
            }
        }
    }

    /** Получить номер последнего batch или null если миграций нет.
     * @return int|null
     */
    private function getLastBatch(): ?int
    {
        $row   = q1('SELECT MAX(batch) AS max_batch FROM migrations');
        $batch = $row['max_batch'] ?? null;
        return $batch !== null ? (int)$batch : null;
    }
}
