<?php

namespace App\Core;

use PDO;
use PDOStatement;

/** Обёртка над PDO с простым интерфейсом для выполнения запросов.
 * Поддерживает позиционные (?) и именованные (:param) плейсхолдеры.
 */
class SQL
{
    private PDO $pdo;

    /** @param PDO|null $pdo Экземпляр PDO; по умолчанию используется синглтон приложения. */
    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? Database::get();
    }

    /** Выполнить SELECT-запрос и вернуть все строки результата.
     * @param string $sql SQL-запрос.
     * @param array<int|string, mixed> $params Позиционные или именованные параметры.
     * @return array<int, array<string, mixed>>
     */
    public function query(string $sql, array $params = []): array
    {
        $stmt = $this->execute($sql, $params);
        return $stmt->fetchAll();
    }

    /** Выполнить SELECT-запрос и вернуть только первую строку результата.
     * @param string $sql SQL-запрос.
     * @param array<int|string, mixed> $params Позиционные или именованные параметры.
     * @return array<string, mixed>|null Null если строка не найдена.
     */
    public function query_once(string $sql, array $params = []): ?array
    {
        $stmt = $this->execute($sql, $params);
        $row  = $stmt->fetch();
        return $row !== false ? $row : null;
    }

    /** Выполнить INSERT, UPDATE или DELETE запрос.
     * Для INSERT возвращает ID вставленной записи, для UPDATE/DELETE — количество затронутых строк.
     * @param string $sql SQL-запрос.
     * @param array<int|string, mixed> $params Позиционные или именованные параметры.
     * @return int ID новой записи или количество затронутых строк.
     */
    public function query_insert(string $sql, array $params = []): int
    {
        $stmt = $this->execute($sql, $params);

        $verb = strtoupper(substr(ltrim($sql), 0, 6));

        if ($verb === 'INSERT') {
            return (int) $this->pdo->lastInsertId();
        }

        return $stmt->rowCount();
    }

    /** Выполнить callable внутри транзакции. При исключении — откат.
     * @param callable $callback Функция с операциями БД.
     * @return mixed Возвращаемое значение callback.
     * @throws \Throwable При ошибке внутри транзакции.
     */
    public function transaction(callable $callback): mixed
    {
        $this->pdo->beginTransaction();
        try {
            $result = $callback();
            if ($this->pdo->inTransaction()) {
                $this->pdo->commit();
            }
            return $result;
        } catch (\Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    /** Подготовить и выполнить параметризованный SQL-запрос.
     * @param string $sql
     * @param array<int|string, mixed> $params
     * @return PDOStatement
     */
    private function execute(string $sql, array $params): PDOStatement
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }
}
