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

    /** OrderBy строит ORDER BY col ASC. */
    public function testOrderByDefault(): void
    {
        $result = (new QueryBuilder('users'))->orderBy('name')->toSql();
        $this->assertSame('SELECT * FROM users ORDER BY name ASC', $result['sql']);
    }

    /** OrderBy DESC. */
    public function testOrderByDesc(): void
    {
        $result = (new QueryBuilder('users'))->orderBy('name', 'DESC')->toSql();
        $this->assertSame('SELECT * FROM users ORDER BY name DESC', $result['sql']);
    }

    /** Множественные orderBy. */
    public function testMultipleOrderBy(): void
    {
        $result = (new QueryBuilder('users'))->orderBy('last_name')->orderBy('first_name', 'DESC')->toSql();
        $this->assertSame('SELECT * FROM users ORDER BY last_name ASC, first_name DESC', $result['sql']);
    }

    /** Limit и offset. */
    public function testLimitOffset(): void
    {
        $result = (new QueryBuilder('users'))->limit(10)->offset(5)->toSql();
        $this->assertSame('SELECT * FROM users LIMIT 10 OFFSET 5', $result['sql']);
    }

    /** Полная цепочка: where + order + limit. */
    public function testFullChain(): void
    {
        $result = (new QueryBuilder('users'))
            ->where('active', 1)->orderBy('name')->limit(10)->offset(0)->toSql();

        $this->assertSame('SELECT * FROM users WHERE active = ? ORDER BY name ASC LIMIT 10 OFFSET 0', $result['sql']);
        $this->assertSame([1], $result['params']);
    }
}
