<?php

namespace App\Core;

/** Fluent SQL-билдер. Строит и выполняет SQL-запросы через хелперы q()/q1()/qi(). */
class QueryBuilder
{
    /** @var array<int, array{type: string, column: string, operator?: string, value?: mixed, values?: array<mixed>, softDelete?: bool}> */
    protected array $wheres = [];

    /** @var array<int, array{column: string, direction: string}> */
    protected array $orders = [];

    protected ?int $limitValue = null;

    protected ?int $offsetValue = null;

    /** @var array<string> Допустимые операторы для WHERE. */
    private const ALLOWED_OPERATORS = ['=', '!=', '<>', '>', '<', '>=', '<=', 'LIKE'];

    /** @param string $table Имя таблицы. */
    public function __construct(protected string $table) {}

    /** Добавить условие WHERE. Множественные вызовы объединяются через AND.
     * @param string $column   Имя колонки.
     * @param mixed  $operator Оператор или значение (тогда оператор '=').
     * @param mixed  $value    Значение (если передан оператор).
     * @return static
     * @throws \InvalidArgumentException Если передан недопустимый оператор.
     */
    public function where(string $column, mixed $operator, mixed $value = null): static
    {
        if (func_num_args() === 2) {
            $value    = $operator;
            $operator = '=';
        }

        if (!in_array(strtoupper((string) $operator), self::ALLOWED_OPERATORS, true)) {
            throw new \InvalidArgumentException("Invalid operator: {$operator}");
        }

        $this->wheres[] = ['type' => 'basic', 'column' => $column, 'operator' => strtoupper((string) $operator), 'value' => $value];
        return $this;
    }

    /** Добавить условие WHERE column IS NULL.
     * @param string $column Имя колонки.
     * @return static
     */
    public function whereNull(string $column): static
    {
        $this->wheres[] = ['type' => 'null', 'column' => $column];
        return $this;
    }

    /** Добавить условие WHERE column IS NOT NULL.
     * @param string $column Имя колонки.
     * @return static
     */
    public function whereNotNull(string $column): static
    {
        $this->wheres[] = ['type' => 'notNull', 'column' => $column];
        return $this;
    }

    /** Добавить условие WHERE column IN (...).
     * @param string              $column Имя колонки.
     * @param array<int, mixed> $values Список значений (не пустой).
     * @return static
     * @throws \InvalidArgumentException Если массив пуст.
     */
    public function whereIn(string $column, array $values): static
    {
        if (empty($values)) {
            throw new \InvalidArgumentException('whereIn() requires a non-empty array.');
        }

        $this->wheres[] = ['type' => 'in', 'column' => $column, 'values' => array_values($values)];
        return $this;
    }

    /** Добавить ORDER BY.
     * @param string $column    Имя колонки.
     * @param string $direction 'ASC' или 'DESC'.
     * @return static
     */
    public function orderBy(string $column, string $direction = 'ASC'): static
    {
        $this->orders[] = ['column' => $column, 'direction' => strtoupper($direction)];
        return $this;
    }

    /** Установить LIMIT.
     * @param int $limit Максимальное количество строк.
     * @return static
     */
    public function limit(int $limit): static
    {
        $this->limitValue = $limit;
        return $this;
    }

    /** Установить OFFSET.
     * @param int $offset Смещение.
     * @return static
     */
    public function offset(int $offset): static
    {
        $this->offsetValue = $offset;
        return $this;
    }

    /** Вернуть сгенерированный SELECT SQL и параметры.
     * @return array{sql: string, params: array<mixed>}
     */
    public function toSql(): array
    {
        return $this->compileSelect();
    }

    /** Скомпилировать SELECT-запрос.
     * @return array{sql: string, params: array<mixed>}
     */
    protected function compileSelect(): array
    {
        $params = [];
        $sql    = "SELECT * FROM {$this->table}";
        $sql   .= $this->compileWheres($params);
        $sql   .= $this->compileOrders();
        $sql   .= $this->compileLimit();

        return ['sql' => $sql, 'params' => $params];
    }

    /** Скомпилировать WHERE-часть запроса.
     * @param array<mixed> &$params Массив параметров (заполняется по ссылке).
     * @return string SQL-фрагмент начиная с ' WHERE ...' или пустая строка.
     */
    protected function compileWheres(array &$params): string
    {
        if (empty($this->wheres)) {
            return '';
        }

        $clauses = [];
        foreach ($this->wheres as $where) {
            match ($where['type']) {
                'basic' => (function () use ($where, &$clauses, &$params) {
                    $clauses[] = "{$where['column']} {$where['operator']} ?";
                    $params[]  = $where['value'];
                })(),
                'null'    => $clauses[] = "{$where['column']} IS NULL",
                'notNull' => $clauses[] = "{$where['column']} IS NOT NULL",
                'in' => (function () use ($where, &$clauses, &$params) {
                    $placeholders = implode(', ', array_fill(0, count($where['values']), '?'));
                    $clauses[]    = "{$where['column']} IN ({$placeholders})";
                    foreach ($where['values'] as $v) {
                        $params[] = $v;
                    }
                })(),
            };
        }

        return ' WHERE ' . implode(' AND ', $clauses);
    }

    /** Скомпилировать ORDER BY.
     * @return string
     */
    protected function compileOrders(): string
    {
        if (empty($this->orders)) {
            return '';
        }

        $parts = array_map(fn($o) => "{$o['column']} {$o['direction']}", $this->orders);
        return ' ORDER BY ' . implode(', ', $parts);
    }

    /** Скомпилировать LIMIT/OFFSET (raw integers, не bound-параметры).
     * @return string
     */
    protected function compileLimit(): string
    {
        $sql = '';
        if ($this->limitValue !== null) {
            $sql .= " LIMIT {$this->limitValue}";
        }
        if ($this->offsetValue !== null) {
            $sql .= " OFFSET {$this->offsetValue}";
        }
        return $sql;
    }
}
