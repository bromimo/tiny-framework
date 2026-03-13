<?php

namespace Tests\Unit\Abstracts;

use App\Facades\DB;
use App\Core\Database;
use App\Abstracts\BaseModel;
use PHPUnit\Framework\TestCase;

/** Модель-заглушка с включённым мягким удалением. */
class SoftStub extends BaseModel
{
    protected static string $table      = 'soft_stubs';
    protected static array  $fillable   = ['name'];
    protected static bool   $softDelete = true;
}

/** Модель-заглушка без мягкого удаления (для проверки guard-поведения). */
class HardStub extends BaseModel
{
    protected static string $table      = 'soft_stubs';
    protected static array  $fillable   = ['name'];
    protected static bool   $softDelete = false;
}

class BaseModelSoftDeleteTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        Database::reset();
        DB::reset();
        qi('CREATE TABLE IF NOT EXISTS soft_stubs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            deleted_at DATETIME NULL DEFAULT NULL
        )');
    }

    public static function tearDownAfterClass(): void
    {
        qi('DROP TABLE IF EXISTS soft_stubs');
    }

    protected function setUp(): void
    {
        qi('TRUNCATE TABLE soft_stubs');
    }

    // deleteById

    public function test_delete_by_id_sets_deleted_at_when_soft_delete_enabled(): void
    {
        qi("INSERT INTO soft_stubs (name) VALUES ('Alice')");
        $id = (int) q1("SELECT id FROM soft_stubs WHERE name = 'Alice'")['id'];

        SoftStub::deleteById($id);

        $row = q1("SELECT * FROM soft_stubs WHERE id = ?", [$id]);
        $this->assertNotNull($row['deleted_at']);
    }

    public function test_delete_by_id_hard_deletes_when_soft_delete_disabled(): void
    {
        qi("INSERT INTO soft_stubs (name) VALUES ('Bob')");
        $id = (int) q1("SELECT id FROM soft_stubs WHERE name = 'Bob'")['id'];

        HardStub::deleteById($id);

        $row = q1("SELECT * FROM soft_stubs WHERE id = ?", [$id]);
        $this->assertNull($row);
    }

    // findById

    public function test_find_by_id_excludes_soft_deleted(): void
    {
        qi("INSERT INTO soft_stubs (name, deleted_at) VALUES ('Deleted', NOW())");
        $id = (int) q1("SELECT id FROM soft_stubs WHERE name = 'Deleted'")['id'];

        $this->assertNull(SoftStub::findById($id));
    }

    public function test_find_by_id_returns_non_deleted_record(): void
    {
        qi("INSERT INTO soft_stubs (name) VALUES ('Active')");
        $id = (int) q1("SELECT id FROM soft_stubs WHERE name = 'Active'")['id'];

        $this->assertNotNull(SoftStub::findById($id));
    }

    // findByField

    public function test_find_by_field_excludes_soft_deleted(): void
    {
        qi("INSERT INTO soft_stubs (name, deleted_at) VALUES ('UniqueDeleted', NOW())");

        $result = SoftStub::findByField('name', 'UniqueDeleted');

        $this->assertNull($result);
    }

    // findAll

    public function test_find_all_excludes_soft_deleted(): void
    {
        qi("INSERT INTO soft_stubs (name) VALUES ('Alive')");
        qi("INSERT INTO soft_stubs (name, deleted_at) VALUES ('Gone', NOW())");

        $results = SoftStub::findAll();

        $this->assertCount(1, $results);
        $this->assertSame('Alive', $results[0]->name);
    }

    // restore

    public function test_restore_clears_deleted_at(): void
    {
        qi("INSERT INTO soft_stubs (name, deleted_at) VALUES ('Restored', NOW())");
        $id = (int) q1("SELECT id FROM soft_stubs WHERE name = 'Restored'")['id'];

        SoftStub::restore($id);

        $row = q1("SELECT * FROM soft_stubs WHERE id = ?", [$id]);
        $this->assertNull($row['deleted_at']);
    }

    public function test_restore_throws_logic_exception_when_soft_delete_disabled(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('restore() called on model without soft delete enabled');
        HardStub::restore(1);
    }

    // forceDelete

    public function test_force_delete_hard_deletes_regardless_of_soft_delete(): void
    {
        qi("INSERT INTO soft_stubs (name) VALUES ('ForceMe')");
        $id = (int) q1("SELECT id FROM soft_stubs WHERE name = 'ForceMe'")['id'];

        SoftStub::forceDelete($id);

        $this->assertNull(q1("SELECT * FROM soft_stubs WHERE id = ?", [$id]));
    }

    // withTrashed

    public function test_with_trashed_returns_all_including_soft_deleted(): void
    {
        qi("INSERT INTO soft_stubs (name) VALUES ('Active2')");
        qi("INSERT INTO soft_stubs (name, deleted_at) VALUES ('Deleted2', NOW())");

        $results = SoftStub::withTrashed();

        $this->assertCount(2, $results);
    }

    public function test_with_trashed_on_non_soft_delete_model_behaves_as_find_all(): void
    {
        qi("INSERT INTO soft_stubs (name) VALUES ('Plain')");

        $results = HardStub::withTrashed();

        $this->assertCount(1, $results);
    }
}
