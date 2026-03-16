<?php

namespace App\Facades;

use App\Core\SQL;
use App\Core\QueryBuilder;

/** Статический фасад над классом SQL.
 * Хранит единственный экземпляр SQL и проксирует вызовы к нему.
 */
class DB
{
    /** @var SQL|null Единственный экземпляр SQL, инициализируется при первом обращении. */
    private static ?SQL $instance = null;

    /** Вернуть общий экземпляр SQL, создав его при первом вызове. */
    private static function instance(): SQL
    {
        if (self::$instance === null) {
            self::$instance = new SQL();
        }

        return self::$instance;
    }

    /** Выполнить SELECT-запрос и вернуть все строки результата.
     * @param string $sql SQL-запрос.
     * @param array<int|string, mixed> $params Позиционные или именованные параметры.
     * @return array<int, array<string, mixed>>
     */
    public static function query(string $sql, array $params = []): array
    {
        return self::instance()->query($sql, $params);
    }

    /** Выполнить SELECT-запрос и вернуть только первую строку результата.
     * @param string $sql SQL-запрос.
     * @param array<int|string, mixed> $params Позиционные или именованные параметры.
     * @return array<string, mixed>|null Null если строка не найдена.
     */
    public static function query_once(string $sql, array $params = []): ?array
    {
        return self::instance()->query_once($sql, $params);
    }

    /** Выполнить INSERT, UPDATE или DELETE запрос.
     * Для INSERT возвращает ID вставленной записи, для UPDATE/DELETE — количество затронутых строк.
     * @param string $sql SQL-запрос.
     * @param array<int|string, mixed> $params Позиционные или именованные параметры.
     * @return int ID новой записи или количество затронутых строк.
     */
    public static function query_insert(string $sql, array $params = []): int
    {
        return self::instance()->query_insert($sql, $params);
    }

    /** Выполнить callable внутри транзакции. При исключении — откат.
     * @param callable $callback Функция с операциями БД.
     * @return mixed Возвращаемое значение callback.
     * @throws \Throwable При ошибке внутри транзакции.
     */
    public static function transaction(callable $callback): mixed
    {
        return self::instance()->transaction($callback);
    }

    /** Создать QueryBuilder для указанной таблицы.
     * @param string $table Имя таблицы.
     * @return QueryBuilder
     */
    public static function table(string $table): QueryBuilder
    {
        return new QueryBuilder($table);
    }

    /** Сбросить экземпляр SQL. Используется в тестах для пересоздания соединения. */
    public static function reset(): void
    {
        self::$instance = null;
    }
}
