<?php

namespace Tests\Unit\Abstracts;

use App\Abstracts\BaseModel;
use PHPUnit\Framework\TestCase;

/** Конкретная модель-заглушка для тестирования BaseModel. */
class StubModel extends BaseModel
{
    protected static string $table      = 'stubs';
    protected static array  $fillable   = ['name', 'email', 'secret'];
    protected static array  $hidden     = ['secret'];
    protected static bool   $softDelete = false;
}

class BaseModelTest extends TestCase
{
    public function test_to_array_returns_all_attributes(): void
    {
        $model = new StubModel(['name' => 'John', 'email' => 'john@example.com', 'secret' => 'pw']);

        $this->assertSame([
            'name'   => 'John',
            'email'  => 'john@example.com',
            'secret' => 'pw',
        ], $model->toArray());
    }

    public function test_json_serialize_excludes_hidden_fields(): void
    {
        $model = new StubModel(['name' => 'John', 'email' => 'john@example.com', 'secret' => 'pw']);

        $data = $model->jsonSerialize();

        $this->assertArrayHasKey('name', $data);
        $this->assertArrayHasKey('email', $data);
        $this->assertArrayNotHasKey('secret', $data);
    }

    public function test_json_encode_excludes_hidden_fields(): void
    {
        $model = new StubModel(['name' => 'John', 'email' => 'john@example.com', 'secret' => 'pw']);

        $decoded = json_decode(json_encode($model), true);

        $this->assertArrayHasKey('name', $decoded);
        $this->assertArrayNotHasKey('secret', $decoded);
    }

    public function test_to_array_is_not_affected_by_hidden(): void
    {
        $model = new StubModel(['name' => 'John', 'secret' => 'pw']);

        $this->assertArrayHasKey('secret', $model->toArray());
    }

    public function test_magic_get_returns_attribute(): void
    {
        $model = new StubModel(['name' => 'John']);

        $this->assertSame('John', $model->name);
    }

    public function test_magic_get_returns_null_for_missing(): void
    {
        $model = new StubModel([]);

        $this->assertNull($model->nonexistent);
    }

    public function test_json_serialize_without_hidden_returns_all_attributes(): void
    {
        $model = new class(['name' => 'John', 'email' => 'john@example.com']) extends BaseModel {
            protected static string $table = 'test';
        };

        $this->assertSame(
            ['name' => 'John', 'email' => 'john@example.com'],
            $model->jsonSerialize()
        );
    }
}
