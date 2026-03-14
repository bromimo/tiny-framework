<?php

namespace Tests\Unit\Abstracts;

use App\Facades\DB;
use App\Core\Database;
use TinyRouter\Http\Method;
use App\Abstracts\BaseModel;
use TinyRouter\Http\Request;
use PHPUnit\Framework\TestCase;

/** Модель-заглушка для тестирования пагинации. */
class PageStub extends BaseModel
{
    protected static string $table     = 'page_stubs';
    protected static array  $fillable  = ['name'];
    protected static bool   $softDelete = false;
}

/** Модель-заглушка с мягким удалением для тестирования фильтрации в paginate(). */
class SoftPageStub extends BaseModel
{
    protected static string $table     = 'page_stubs';
    protected static array  $fillable  = ['name'];
    protected static bool   $softDelete = true;
}

class BaseModelPaginateTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        Database::reset();
        DB::reset();
        qi('CREATE TABLE IF NOT EXISTS page_stubs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            deleted_at DATETIME NULL DEFAULT NULL
        )');
    }

    public static function tearDownAfterClass(): void
    {
        qi('DROP TABLE IF EXISTS page_stubs');
    }

    protected function setUp(): void
    {
        qi('TRUNCATE TABLE page_stubs');
    }

    private function insertRows(int $count): void
    {
        for ($i = 1; $i <= $count; $i++) {
            qi("INSERT INTO page_stubs (name) VALUES (?)", ["Row{$i}"]);
        }
    }

    /** Создать Request с query-параметрами пагинации. */
    private function request(array $query = []): Request
    {
        return new Request(Method::GET, '/', $query, [], []);
    }

    // -------------------------------------------------------------------------
    // meta values

    public function test_paginate_returns_correct_meta_for_full_page(): void
    {
        $this->insertRows(30);

        $result = PageStub::paginate($this->request(['page' => 1, 'per_page' => 10]));

        $this->assertSame(30, $result['meta']['total']);
        $this->assertSame(10, $result['meta']['per_page']);
        $this->assertSame(1,  $result['meta']['current_page']);
        $this->assertSame(3,  $result['meta']['last_page']);
    }

    public function test_paginate_returns_correct_data_count_per_page(): void
    {
        $this->insertRows(25);

        $result = PageStub::paginate($this->request(['page' => 1, 'per_page' => 10]));

        $this->assertCount(10, $result['data']);
    }

    public function test_paginate_last_page_returns_remainder(): void
    {
        $this->insertRows(25);

        $result = PageStub::paginate($this->request(['page' => 3, 'per_page' => 10]));

        $this->assertCount(5, $result['data']);
    }

    public function test_paginate_page_beyond_last_returns_empty_data(): void
    {
        $this->insertRows(5);

        $result = PageStub::paginate($this->request(['page' => 99, 'per_page' => 10]));

        $this->assertCount(0, $result['data']);
        $this->assertSame(5,  $result['meta']['total']);
        $this->assertSame(99, $result['meta']['current_page']);
    }

    public function test_paginate_empty_table_returns_zero_total(): void
    {
        $result = PageStub::paginate($this->request());

        $this->assertSame(0, $result['meta']['total']);
        $this->assertSame(1, $result['meta']['last_page']);
        $this->assertCount(0, $result['data']);
    }

    // -------------------------------------------------------------------------
    // clamping

    public function test_paginate_clamps_page_zero_to_one(): void
    {
        $this->insertRows(5);

        $result = PageStub::paginate($this->request(['page' => 0, 'per_page' => 10]));

        $this->assertSame(1, $result['meta']['current_page']);
        $this->assertCount(5, $result['data']);
    }

    public function test_paginate_clamps_per_page_above_100(): void
    {
        $this->insertRows(5);

        $result = PageStub::paginate($this->request(['page' => 1, 'per_page' => 200]));

        $this->assertSame(100, $result['meta']['per_page']);
    }

    public function test_paginate_clamps_per_page_zero_to_one(): void
    {
        $this->insertRows(3);

        $result = PageStub::paginate($this->request(['page' => 1, 'per_page' => 0]));

        $this->assertSame(1, $result['meta']['per_page']);
    }

    // -------------------------------------------------------------------------
    // soft delete filter

    public function test_paginate_excludes_soft_deleted_when_soft_delete_enabled(): void
    {
        $this->insertRows(3);
        qi("INSERT INTO page_stubs (name, deleted_at) VALUES ('Deleted', NOW())");

        $result = SoftPageStub::paginate($this->request(['page' => 1, 'per_page' => 10]));

        $this->assertSame(3, $result['meta']['total']);
        $this->assertCount(3, $result['data']);
    }

    // -------------------------------------------------------------------------
    // return types

    public function test_paginate_data_contains_model_instances(): void
    {
        $this->insertRows(2);

        $result = PageStub::paginate($this->request(['page' => 1, 'per_page' => 10]));

        $this->assertInstanceOf(PageStub::class, $result['data'][0]);
    }
}
