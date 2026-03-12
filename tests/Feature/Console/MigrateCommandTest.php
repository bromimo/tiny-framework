<?php

namespace Tests\Feature\Console;

use App\Core\Database;
use App\Facades\DB;
use PHPUnit\Framework\TestCase;
use App\Console\Commands\Migrate\MigrateCommand;

class MigrateCommandTest extends TestCase
{
    private string $tmpMigrations;

    protected function setUp(): void
    {
        Database::reset();
        DB::reset();

        $this->tmpMigrations = sys_get_temp_dir() . '/test_migrations_' . uniqid();
        mkdir($this->tmpMigrations, 0777, true);

        qi("DROP TABLE IF EXISTS migrations");
        qi("DROP TABLE IF EXISTS test_migrate_cmd");
    }

    protected function tearDown(): void
    {
        array_map('unlink', glob($this->tmpMigrations . '/*.php') ?: []);
        rmdir($this->tmpMigrations);

        qi("DROP TABLE IF EXISTS migrations");
        qi("DROP TABLE IF EXISTS test_migrate_cmd");
    }

    private function makeCommand(): MigrateCommand
    {
        return new MigrateCommand($this->tmpMigrations);
    }

    private function addMigration(string $name, string $upSql, string $downSql = ''): void
    {
        $content = "<?php\nreturn new class {\n"
            . "    public function up(): void { qi(\"{$upSql}\"); }\n"
            . "    public function down(): void {" . ($downSql ? " qi(\"{$downSql}\");" : '') . " }\n"
            . "};\n";

        file_put_contents($this->tmpMigrations . '/' . $name . '.php', $content);
    }

    public function test_runs_pending_migrations(): void
    {
        $this->addMigration(
            '2026_01_01_000001_create_test_migrate_cmd',
            'CREATE TABLE IF NOT EXISTS test_migrate_cmd (id INT)',
            'DROP TABLE IF EXISTS test_migrate_cmd'
        );

        $this->expectOutputRegex('/Migrated: 2026_01_01_000001_create_test_migrate_cmd/');
        $this->makeCommand()->handle([]);

        $row = q1("SHOW TABLES LIKE 'test_migrate_cmd'");
        $this->assertNotNull($row);
    }

    public function test_records_migration_in_table(): void
    {
        $this->addMigration('2026_01_01_000001_create_test_migrate_cmd', 'CREATE TABLE IF NOT EXISTS test_migrate_cmd (id INT)');

        $this->makeCommand()->handle([]);

        $row = q1("SELECT * FROM migrations WHERE migration = '2026_01_01_000001_create_test_migrate_cmd'");
        $this->assertNotNull($row);
        $this->assertSame(1, (int)$row['batch']);
    }

    public function test_does_not_run_already_ran_migrations(): void
    {
        $this->addMigration('2026_01_01_000001_create_test_migrate_cmd', 'CREATE TABLE IF NOT EXISTS test_migrate_cmd (id INT)');

        $this->makeCommand()->handle([]);
        $this->expectOutputContains('Nothing to migrate.');
        $this->makeCommand()->handle([]);
    }

    public function test_increments_batch_number(): void
    {
        $this->addMigration('2026_01_01_000001_create_test_migrate_cmd', 'CREATE TABLE IF NOT EXISTS test_migrate_cmd (id INT)');
        $this->makeCommand()->handle([]);

        $this->addMigration('2026_01_01_000002_alter_test_migrate_cmd', 'ALTER TABLE test_migrate_cmd ADD COLUMN name VARCHAR(100)');
        $this->makeCommand()->handle([]);

        $row = q1("SELECT batch FROM migrations WHERE migration = '2026_01_01_000002_alter_test_migrate_cmd'");
        $this->assertSame(2, (int)$row['batch']);
    }

    public function test_outputs_nothing_to_migrate_when_no_pending(): void
    {
        $this->expectOutputContains('Nothing to migrate.');
        $this->makeCommand()->handle([]);
    }

    private function expectOutputContains(string $expected): void
    {
        $this->expectOutputRegex('/' . preg_quote($expected, '/') . '/');
    }
}
