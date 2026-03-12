<?php

namespace App\Console\Commands\Migrate;

/** Создаёт таблицу migrations если не существует.
 * Используется в MigrateCommand и MigrateRollbackCommand.
 */
trait EnsuresMigrationsTable
{
    /** Создать таблицу migrations если не существует. */
    private function ensureMigrationsTable(): void
    {
        qi('CREATE TABLE IF NOT EXISTS migrations (
            id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            migration  VARCHAR(255) NOT NULL,
            batch      INT UNSIGNED NOT NULL,
            ran_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )');
    }
}
