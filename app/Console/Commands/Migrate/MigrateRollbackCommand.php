<?php

namespace App\Console\Commands\Migrate;

use Throwable;
use RuntimeException;
use App\Abstracts\BaseCommand;
use App\Facades\DB;

/** Откатывает все миграции последнего batch в обратном порядке. */
class MigrateRollbackCommand extends BaseCommand
{
    use EnsuresMigrationsTable;

    public static string $name = 'migrate:rollback';

    private string $migrationsPath;

    /** @param string|null $migrationsPath Путь к директории миграций (для тестов). */
    public function __construct(?string $migrationsPath = null)
    {
        $this->migrationsPath = $migrationsPath ?? __DIR__ . '/../../../../database/migrations';
    }

    /** Вернуть краткое описание команды. */
    public function description(): string
    {
        return 'Откатить миграции последнего batch.';
    }

    /** Выполнить команду.
     * @param array<int, string> $args Аргументы командной строки.
     */
    public function handle(array $args): void
    {
        $this->ensureMigrationsTable();
        $batch = $this->getLastBatch();

        if ($batch === null) {
            echo 'Nothing to rollback.' . PHP_EOL;
            return;
        }

        $migrations = q("SELECT * FROM {$this->migrationsTable} WHERE batch = ? ORDER BY id DESC", [$batch]);

        foreach ($migrations as $row) {
            $name = $row['migration'];
            $file = $this->migrationsPath . '/' . $name . '.php';

            if (!file_exists($file)) {
                throw new RuntimeException("Migration file not found: {$name}.php");
            }

            try {
                DB::transaction(function () use ($file, $row) {
                    $migration = (static fn($f) => require $f)($file);
                    $migration->down();
                    qi("DELETE FROM {$this->migrationsTable} WHERE id = ?", [$row['id']]);
                });
                echo self::YELLOW . 'Rolled back: ' . self::RESET . $name . PHP_EOL;
            } catch (Throwable $e) {
                throw new RuntimeException("Error rolling back {$name}: " . $e->getMessage());
            }
        }
    }

    /** Получить номер последнего batch или null если миграций нет.
     * @return int|null
     */
    private function getLastBatch(): ?int
    {
        $row   = q1("SELECT MAX(batch) AS max_batch FROM {$this->migrationsTable}");
        $batch = $row['max_batch'] ?? null;
        return $batch !== null ? (int)$batch : null;
    }
}
