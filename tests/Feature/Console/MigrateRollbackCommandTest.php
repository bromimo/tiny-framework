<?php

namespace Tests\Feature\Console;

use App\Facades\DB;
use App\Core\Database;
use PHPUnit\Framework\TestCase;
use App\Console\Commands\Migrate\MigrateCommand;
use App\Console\Commands\Migrate\MigrateRollbackCommand;

class MigrateRollbackCommandTest extends TestCase
{
    private string $tmpMigrations;

    protected function setUp(): void
    {
        Database::reset();
        DB::reset();

        $this->tmpMigrations = sys_get_temp_dir() . '/test_rollback_' . uniqid();
        mkdir($this->tmpMigrations, 0777, true);

        qi("DROP TABLE IF EXISTS migrations");
        qi("DROP TABLE IF EXISTS test_rollback_tbl");
    }

    protected function tearDown(): void
    {
        array_map('unlink', glob($this->tmpMigrations . '/*.php') ?: []);
        rmdir($this->tmpMigrations);

        qi("DROP TABLE IF EXISTS migrations");
        qi("DROP TABLE IF EXISTS test_rollback_tbl");
    }

    private function addMigration(string $name, string $upSql, string $downSql): void
    {
        $content = "<?php\nreturn new class {\n"
            . "    public function up(): void { qi(\"{$upSql}\"); }\n"
            . "    public function down(): void { qi(\"{$downSql}\"); }\n"
            . "};\n";

        file_put_contents($this->tmpMigrations . '/' . $name . '.php', $content);
    }

    private function migrate(): void
    {
        (new MigrateCommand($this->tmpMigrations))->handle([]);
    }

    private function rollback(): void
    {
        (new MigrateRollbackCommand($this->tmpMigrations))->handle([]);
    }

    public function test_rolls_back_last_batch(): void
    {
        $this->addMigration(
            '2026_01_01_000001_create_test_rollback_tbl',
            'CREATE TABLE IF NOT EXISTS test_rollback_tbl (id INT)',
            'DROP TABLE IF EXISTS test_rollback_tbl'
        );

        $this->migrate();
        $this->expectOutputRegex('/Rolled back: 2026_01_01_000001_create_test_rollback_tbl/');
        $this->rollback();

        $row = q1("SHOW TABLES LIKE 'test_rollback_tbl'");
        $this->assertNull($row);
    }

    public function test_removes_migration_record_from_table(): void
    {
        $this->addMigration(
            '2026_01_01_000001_create_test_rollback_tbl',
            'CREATE TABLE IF NOT EXISTS test_rollback_tbl (id INT)',
            'DROP TABLE IF EXISTS test_rollback_tbl'
        );

        $this->migrate();
        $this->rollback();

        $row = q1("SELECT * FROM migrations WHERE migration = '2026_01_01_000001_create_test_rollback_tbl'");
        $this->assertNull($row);
    }

    public function test_outputs_nothing_to_rollback_when_empty(): void
    {
        // migrate создаёт таблицу и выводит "Nothing to migrate." (нет файлов в tmpMigrations)
        $this->migrate();

        $this->expectOutputRegex('/Nothing to rollback\./');
        $this->rollback();
    }

    public function test_only_rolls_back_last_batch(): void
    {
        $this->addMigration(
            '2026_01_01_000001_create_test_rollback_tbl',
            'CREATE TABLE IF NOT EXISTS test_rollback_tbl (id INT)',
            'DROP TABLE IF EXISTS test_rollback_tbl'
        );
        $this->migrate();

        $this->addMigration(
            '2026_01_01_000002_alter_test_rollback_tbl',
            'ALTER TABLE test_rollback_tbl ADD COLUMN name VARCHAR(100)',
            'ALTER TABLE test_rollback_tbl DROP COLUMN name'
        );
        $this->migrate();

        $this->rollback();

        $batch1 = q1("SELECT * FROM migrations WHERE migration = '2026_01_01_000001_create_test_rollback_tbl'");
        $batch2 = q1("SELECT * FROM migrations WHERE migration = '2026_01_01_000002_alter_test_rollback_tbl'");

        $this->assertNotNull($batch1);
        $this->assertNull($batch2);
    }
}
