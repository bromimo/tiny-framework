<?php

namespace Tests\Unit\Core;

use App\Core\QueryBuilder;
use PHPUnit\Framework\TestCase;

class QueryBuilderTest extends TestCase
{
    /** Базовый where с двумя аргументами строит WHERE col = ?. */
    public function testWhereBasic(): void
    {
        $qb = new QueryBuilder('users');
        $result = $qb->where('email', 'test@example.com')->toSql();

        $this->assertSame('SELECT * FROM users WHERE email = ?', $result['sql']);
        $this->assertSame(['test@example.com'], $result['params']);
    }

    /** Where с тремя аргументами использует указанный оператор. */
    public function testWhereWithOperator(): void
    {
        $qb = new QueryBuilder('users');
        $result = $qb->where('age', '>', 18)->toSql();

        $this->assertSame('SELECT * FROM users WHERE age > ?', $result['sql']);
        $this->assertSame([18], $result['params']);
    }

    /** Where с оператором LIKE. */
    public function testWhereWithLike(): void
    {
        $qb = new QueryBuilder('users');
        $result = $qb->where('name', 'LIKE', '%John%')->toSql();

        $this->assertSame('SELECT * FROM users WHERE name LIKE ?', $result['sql']);
        $this->assertSame(['%John%'], $result['params']);
    }

    /** Множественные where объединяются через AND. */
    public function testMultipleWhere(): void
    {
        $qb = new QueryBuilder('users');
        $result = $qb->where('age', '>', 18)->where('active', 1)->toSql();

        $this->assertSame('SELECT * FROM users WHERE age > ? AND active = ?', $result['sql']);
        $this->assertSame([18, 1], $result['params']);
    }

    /** Where с недопустимым оператором бросает InvalidArgumentException. */
    public function testWhereInvalidOperator(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        (new QueryBuilder('users'))->where('id', 'INVALID', 1);
    }

    /** WhereNull строит WHERE col IS NULL. */
    public function testWhereNull(): void
    {
        $qb = new QueryBuilder('users');
        $result = $qb->whereNull('deleted_at')->toSql();

        $this->assertSame('SELECT * FROM users WHERE deleted_at IS NULL', $result['sql']);
        $this->assertSame([], $result['params']);
    }

    /** WhereNotNull строит WHERE col IS NOT NULL. */
    public function testWhereNotNull(): void
    {
        $qb = new QueryBuilder('users');
        $result = $qb->whereNotNull('deleted_at')->toSql();

        $this->assertSame('SELECT * FROM users WHERE deleted_at IS NOT NULL', $result['sql']);
        $this->assertSame([], $result['params']);
    }

    /** WhereIn строит WHERE col IN (?, ?, ?). */
    public function testWhereIn(): void
    {
        $qb = new QueryBuilder('users');
        $result = $qb->whereIn('id', [1, 2, 3])->toSql();

        $this->assertSame('SELECT * FROM users WHERE id IN (?, ?, ?)', $result['sql']);
        $this->assertSame([1, 2, 3], $result['params']);
    }

    /** WhereIn с одним элементом строит WHERE col IN (?). */
    public function testWhereInSingle(): void
    {
        $qb = new QueryBuilder('users');
        $result = $qb->whereIn('id', [42])->toSql();

        $this->assertSame('SELECT * FROM users WHERE id IN (?)', $result['sql']);
        $this->assertSame([42], $result['params']);
    }

    /** WhereIn с пустым массивом бросает InvalidArgumentException. */
    public function testWhereInEmpty(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        (new QueryBuilder('users'))->whereIn('id', []);
    }
}
