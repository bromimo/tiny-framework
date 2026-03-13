<?php

namespace Tests\Unit\Core;

use App\Core\SQL;
use PDO;
use PDOStatement;
use PHPUnit\Framework\TestCase;

class SqlTest extends TestCase
{
    private PDO $pdo;
    private PDOStatement $stmt;
    private SQL $sql;

    protected function setUp(): void
    {
        $this->pdo  = $this->createMock(PDO::class);
        $this->stmt = $this->createMock(PDOStatement::class);
        $this->pdo->method('prepare')->willReturn($this->stmt);
        $this->sql  = new SQL($this->pdo);
    }

    public function test_query_returns_all_rows(): void
    {
        $rows = [['id' => 1, 'name' => 'John'], ['id' => 2, 'name' => 'Jane']];
        $this->stmt->method('fetchAll')->willReturn($rows);

        $this->assertSame($rows, $this->sql->query('SELECT * FROM users'));
    }

    public function test_query_returns_empty_array_when_no_rows(): void
    {
        $this->stmt->method('fetchAll')->willReturn([]);

        $this->assertSame([], $this->sql->query('SELECT * FROM users WHERE 1=0'));
    }

    public function test_query_once_returns_row(): void
    {
        $row = ['id' => 1, 'name' => 'John'];
        $this->stmt->method('fetch')->willReturn($row);

        $this->assertSame($row, $this->sql->query_once('SELECT * FROM users WHERE id = ?', [1]));
    }

    public function test_query_once_returns_null_when_not_found(): void
    {
        $this->stmt->method('fetch')->willReturn(false);

        $this->assertNull($this->sql->query_once('SELECT * FROM users WHERE id = ?', [999]));
    }

    public function test_query_insert_returns_last_insert_id(): void
    {
        $this->pdo->method('lastInsertId')->willReturn('42');

        $this->assertSame(42, $this->sql->query_insert('INSERT INTO users (name) VALUES (?)', ['John']));
    }

    public function test_query_insert_returns_row_count_for_update(): void
    {
        $this->stmt->method('rowCount')->willReturn(3);

        $this->assertSame(3, $this->sql->query_insert('UPDATE users SET name = ?', ['John']));
    }

    public function test_query_insert_returns_row_count_for_delete(): void
    {
        $this->stmt->method('rowCount')->willReturn(1);

        $this->assertSame(1, $this->sql->query_insert('DELETE FROM users WHERE id = ?', [1]));
    }

    public function test_execute_passes_params_to_statement(): void
    {
        $params = ['John', 'john@example.com'];
        $this->stmt->expects($this->once())->method('execute')->with($params);
        $this->stmt->method('fetchAll')->willReturn([]);

        $this->sql->query('SELECT * FROM users WHERE name = ? AND email = ?', $params);
    }
}
