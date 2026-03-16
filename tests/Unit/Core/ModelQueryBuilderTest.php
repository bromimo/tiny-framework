<?php

namespace Tests\Unit\Core;

use App\Abstracts\BaseModel;
use App\Core\ModelQueryBuilder;
use PHPUnit\Framework\TestCase;

class SoftStubModel extends BaseModel
{
    protected static string $table      = 'soft_stub_models';
    protected static array  $fillable   = ['name'];
    protected static bool   $softDelete = true;
}

class HardStubModel extends BaseModel
{
    protected static string $table      = 'hard_stub_models';
    protected static array  $fillable   = ['name'];
    protected static bool   $softDelete = false;
}

class ModelQueryBuilderTest extends TestCase
{
    /** Soft delete модель автоматически добавляет WHERE deleted_at IS NULL. */
    public function testSoftDeleteAutoFilter(): void
    {
        $qb = new ModelQueryBuilder(SoftStubModel::class);
        $result = $qb->toSql();

        $this->assertSame('SELECT * FROM soft_stub_models WHERE deleted_at IS NULL', $result['sql']);
        $this->assertSame([], $result['params']);
    }

    /** withTrashed убирает soft delete фильтр. */
    public function testWithTrashed(): void
    {
        $qb = new ModelQueryBuilder(SoftStubModel::class);
        $result = $qb->withTrashed()->toSql();

        $this->assertSame('SELECT * FROM soft_stub_models', $result['sql']);
    }

    /** Не-soft-delete модель не добавляет фильтр. */
    public function testNoSoftDeleteNoFilter(): void
    {
        $qb = new ModelQueryBuilder(HardStubModel::class);
        $result = $qb->toSql();

        $this->assertSame('SELECT * FROM hard_stub_models', $result['sql']);
    }

    /** Where + soft delete фильтр работают вместе. */
    public function testWhereWithSoftDelete(): void
    {
        $qb = new ModelQueryBuilder(SoftStubModel::class);
        $result = $qb->where('name', 'Alice')->toSql();

        $this->assertSame('SELECT * FROM soft_stub_models WHERE deleted_at IS NULL AND name = ?', $result['sql']);
        $this->assertSame(['Alice'], $result['params']);
    }

    /** withTrashed + where возвращает только пользовательский фильтр. */
    public function testWithTrashedAndWhere(): void
    {
        $qb = new ModelQueryBuilder(SoftStubModel::class);
        $result = $qb->withTrashed()->where('name', 'Alice')->toSql();

        $this->assertSame('SELECT * FROM soft_stub_models WHERE name = ?', $result['sql']);
        $this->assertSame(['Alice'], $result['params']);
    }

    /** withTrashed + toSql — soft delete фильтр снят. */
    public function testWithTrashedPaginateSql(): void
    {
        $qb = new ModelQueryBuilder(SoftStubModel::class);
        $result = $qb->withTrashed()->toSql();

        $this->assertStringNotContainsString('deleted_at', $result['sql']);
    }

    /** get() возвращает массив моделей. */
    public function testGetReturnsModels(): void
    {
        qi('CREATE TABLE IF NOT EXISTS hard_stub_models (id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(100))');
        qi('TRUNCATE TABLE hard_stub_models');
        qi("INSERT INTO hard_stub_models (name) VALUES (?)", ['Alice']);

        $results = (new ModelQueryBuilder(HardStubModel::class))->get();

        $this->assertCount(1, $results);
        $this->assertInstanceOf(HardStubModel::class, $results[0]);
        $this->assertSame('Alice', $results[0]->name);

        qi('DROP TABLE IF EXISTS hard_stub_models');
    }

    /** first() возвращает модель или null. */
    public function testFirstReturnsModelOrNull(): void
    {
        qi('CREATE TABLE IF NOT EXISTS hard_stub_models (id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(100))');
        qi('TRUNCATE TABLE hard_stub_models');
        qi("INSERT INTO hard_stub_models (name) VALUES (?)", ['Bob']);

        $model = (new ModelQueryBuilder(HardStubModel::class))->where('name', 'Bob')->first();
        $this->assertInstanceOf(HardStubModel::class, $model);
        $this->assertSame('Bob', $model->name);

        $null = (new ModelQueryBuilder(HardStubModel::class))->where('name', 'Nobody')->first();
        $this->assertNull($null);

        qi('DROP TABLE IF EXISTS hard_stub_models');
    }
}
