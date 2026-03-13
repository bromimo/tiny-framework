<?php

namespace App\Console\Commands\Migrate;

/** Создаёт таблицу миграций если не существует.
 * Используется в MigrateCommand и MigrateRollbackCommand.
 */
trait EnsuresMigrationsTable
{
    private string $migrationsTable;

    /** Инициализировать имя таблицы из конфига и создать её если не существует. */
    private function ensureMigrationsTable(): void
    {
        $this->migrationsTable = config('database.migrations.table', 'migrations');

        qi("CREATE TABLE IF NOT EXISTS {$this->migrationsTable} (
            id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            migration  VARCHAR(255) NOT NULL,
            batch      INT UNSIGNED NOT NULL,
            ran_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");
    }
}
