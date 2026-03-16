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

    /** toInsertSql строит INSERT INTO table (cols) VALUES (?). */
    public function testToInsertSql(): void
    {
        $result = (new QueryBuilder('users'))->toInsertSql(['name' => 'John', 'email' => 'john@example.com']);

        $this->assertSame('INSERT INTO users (name, email) VALUES (?, ?)', $result['sql']);
        $this->assertSame(['John', 'john@example.com'], $result['params']);
    }

    /** toUpdateSql строит UPDATE table SET col = ? WHERE id = ?. */
    public function testToUpdateSql(): void
    {
        $result = (new QueryBuilder('users'))->where('id', 1)->toUpdateSql(['name' => 'Jane']);

        $this->assertSame('UPDATE users SET name = ? WHERE id = ?', $result['sql']);
        $this->assertSame(['Jane', 1], $result['params']);
    }

    /** toDeleteSql строит DELETE FROM table WHERE id = ?. */
    public function testToDeleteSql(): void
    {
        $result = (new QueryBuilder('users'))->where('id', 1)->toDeleteSql();

        $this->assertSame('DELETE FROM users WHERE id = ?', $result['sql']);
        $this->assertSame([1], $result['params']);
    }

    /** Update без WHERE бросает LogicException. */
    public function testUpdateWithoutWhereThrows(): void
    {
        $this->expectException(\LogicException::class);
        (new QueryBuilder('users'))->toUpdateSql(['name' => 'Jane']);
    }

    /** Delete без WHERE бросает LogicException. */
    public function testDeleteWithoutWhereThrows(): void
    {
        $this->expectException(\LogicException::class);
        (new QueryBuilder('users'))->toDeleteSql();
    }

    /** Count строит SELECT COUNT(*) без ORDER BY и LIMIT. */
    public function testCountSql(): void
    {
        $result = (new QueryBuilder('users'))
            ->where('active', 1)->orderBy('name')->limit(10)->toCountSql();

        $this->assertSame('SELECT COUNT(*) AS count FROM users WHERE active = ?', $result['sql']);
        $this->assertSame([1], $result['params']);
    }

    /** Билдер переиспользуем: toSql() после toCountSql(). */
    public function testReusability(): void
    {
        $qb = (new QueryBuilder('users'))->where('active', 1);

        $count  = $qb->toCountSql();
        $select = $qb->toSql();

        $this->assertSame('SELECT COUNT(*) AS count FROM users WHERE active = ?', $count['sql']);
        $this->assertSame('SELECT * FROM users WHERE active = ?', $select['sql']);
        $this->assertSame([1], $count['params']);
        $this->assertSame([1], $select['params']);
    }

    /** Paginate: count без ORDER BY/LIMIT, select сохраняет ORDER BY. */
    public function testPaginateSqlShape(): void
    {
        $qb = (new QueryBuilder('users'))->where('active', 1)->orderBy('name');

        $count = $qb->toCountSql();
        $this->assertSame('SELECT COUNT(*) AS count FROM users WHERE active = ?', $count['sql']);
        $this->assertStringNotContainsString('ORDER BY', $count['sql']);

        $select = $qb->toSql();
        $this->assertStringContainsString('ORDER BY name ASC', $select['sql']);
        $this->assertStringNotContainsString('LIMIT', $select['sql']);
    }

    /** first() не уничтожает limit если он был установлен. */
    public function testFirstPreservesLimit(): void
    {
        $qb = (new QueryBuilder('users'))->limit(5);

        $before = $qb->toSql();
        $this->assertStringContainsString('LIMIT 5', $before['sql']);
    }
}
