<?php

namespace Tests\Unit\Console;

use App\Console\Commands\Make\MakeMigrationCommand;
use PHPUnit\Framework\TestCase;

class MakeMigrationCommandTest extends TestCase
{
    private string $tmpMigrations;
    private string $tmpStubs;

    protected function setUp(): void
    {
        $this->tmpMigrations = sys_get_temp_dir() . '/migrations_' . uniqid();
        $this->tmpStubs      = sys_get_temp_dir() . '/stubs_' . uniqid();
        mkdir($this->tmpMigrations, 0777, true);
        mkdir($this->tmpStubs . '/migrations', 0777, true);

        file_put_contents($this->tmpStubs . '/migrations/migration.stub', "<?php\nreturn new class {\n    public function up(): void {}\n    public function down(): void {}\n};\n");
    }

    protected function tearDown(): void
    {
        array_map('unlink', glob($this->tmpMigrations . '/*.php') ?: []);
        rmdir($this->tmpMigrations);
        unlink($this->tmpStubs . '/migrations/migration.stub');
        rmdir($this->tmpStubs . '/migrations');
        rmdir($this->tmpStubs);
    }

    private function makeCommand(): MakeMigrationCommand
    {
        return new MakeMigrationCommand($this->tmpMigrations, $this->tmpStubs);
    }

    public function test_creates_migration_file(): void
    {
        $this->makeCommand()->handle(['create_users_table']);

        $files = glob($this->tmpMigrations . '/*.php');
        $this->assertCount(1, $files);
        $this->assertMatchesRegularExpression('/\d{4}_\d{2}_\d{2}_\d{6}_create_users_table\.php$/', $files[0]);
    }

    public function test_created_file_contains_stub_content(): void
    {
        $this->makeCommand()->handle(['create_users_table']);

        $files   = glob($this->tmpMigrations . '/*.php');
        $content = file_get_contents($files[0]);
        $this->assertStringContainsString('return new class', $content);
    }

    public function test_uses_custom_stub_via_flag(): void
    {
        $customStub = $this->tmpStubs . '/migrations/custom.stub';
        file_put_contents($customStub, "<?php\n// custom stub\nreturn new class { public function up(): void {} public function down(): void {} };\n");

        $this->makeCommand()->handle(['create_posts_table', '--stub=' . $customStub]);

        $files   = glob($this->tmpMigrations . '/*.php');
        $content = file_get_contents($files[0]);
        $this->assertStringContainsString('custom stub', $content);

        unlink($customStub);
    }

    public function test_exits_without_name(): void
    {
        $this->expectOutputRegex('/Usage:/');

        try {
            $this->makeCommand()->handle([]);
            $this->fail('Expected exit');
        } catch (\Throwable) {
        }
    }
}
