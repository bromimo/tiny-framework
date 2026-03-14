<?php

namespace App\Abstracts;

use LogicException;
use PDOException;
use App\Traits\HasObserver;
use App\Exceptions\QueryException;

/** Базовый класс для всех моделей.
 * Предоставляет стандартные CRUD-операции через хелперы q(), q1(), qi().
 * Подклассы обязаны переопределить свойство {@see $table}.
 */
abstract class BaseModel implements \JsonSerializable
{
    use HasObserver;
    /** @var string Имя таблицы в базе данных. Должно быть переопределено в подклассе. */
    protected static string $table = '';

    /** @var array<int, string> Поля, разрешённые для массовой записи. */
    protected static array $fillable = [];

    /** @var array<int, string> Поля, скрытые при сериализации в JSON. */
    protected static array $hidden = [];

    /** @var bool Включить мягкое удаление (логическое через поле deleted_at). */
    protected static bool $softDelete = false;

    /** @param array<string, mixed> $attributes Атрибуты записи из БД. */
    public function __construct(protected array $attributes = []) {}

    /** Получить значение атрибута по имени.
     * @param string $name Имя атрибута.
     * @return mixed
     */
    public function __get(string $name): mixed
    {
        return $this->attributes[$name] ?? null;
    }

    /** Вернуть атрибуты модели в виде массива.
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->attributes;
    }

    /** Сериализовать модель в JSON (используется json_encode). Скрывает поля из $hidden.
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return static::$hidden
            ? array_diff_key($this->attributes, array_flip(static::$hidden))
            : $this->attributes;
    }

    /** Найти одну запись по первичному ключу.
     * @param int $id ID записи.
     * @return static|null Null если запись не найдена или мягко удалена.
     */
    public static function findById(int $id): ?static
    {
        $filter = static::$softDelete ? ' AND deleted_at IS NULL' : '';
        $data   = q1('SELECT * FROM ' . static::$table . ' WHERE id = ?' . $filter, [$id]);
        return $data ? new static($data) : null;
    }

    /** Найти одну запись по произвольному полю.
     * Поле $field должно быть доверенной строкой (хардкод), не пользовательским вводом.
     * @param string $field Имя поля.
     * @param mixed  $value Искомое значение.
     * @return static|null Null если запись не найдена или мягко удалена.
     */
    public static function findByField(string $field, mixed $value): ?static
    {
        $filter = static::$softDelete ? ' AND deleted_at IS NULL' : '';
        $data   = q1('SELECT * FROM ' . static::$table . ' WHERE ' . $field . ' = ?' . $filter, [$value]);
        return $data ? new static($data) : null;
    }

    /** Вернуть все записи таблицы. Исключает мягко удалённые если $softDelete = true.
     * @return array<int, static>
     */
    public static function findAll(): array
    {
        $where = static::$softDelete ? ' WHERE deleted_at IS NULL' : '';
        return array_map(fn(array $row) => new static($row), q('SELECT * FROM ' . static::$table . $where));
    }

    /** Удалить запись по первичному ключу.
     * Если $softDelete = true — мягкое удаление (устанавливает deleted_at = NOW()).
     * Если $softDelete = false — жёсткое удаление (DELETE).
     * @param int $id ID записи.
     * @return bool True если хотя бы одна строка была затронута. False если обсервер отменил.
     */
    public static function deleteById(int $id): bool
    {
        if (static::fireObserverEvent('deleting', $id) === false) {
            return false;
        }

        if (static::$softDelete) {
            $result = qi('UPDATE ' . static::$table . ' SET deleted_at = NOW() WHERE id = ?', [$id]) > 0;
        } else {
            $result = qi('DELETE FROM ' . static::$table . ' WHERE id = ?', [$id]) > 0;
        }

        if ($result) {
            static::fireObserverEvent('deleted', $id);
        }

        return $result;
    }

    /** Восстановить мягко удалённую запись (очищает deleted_at).
     * @param int $id ID записи.
     * @return void
     * @throws LogicException Если модель не поддерживает мягкое удаление.
     */
    public static function restore(int $id): void
    {
        if (!static::$softDelete) {
            throw new LogicException('restore() called on model without soft delete enabled');
        }
        qi('UPDATE ' . static::$table . ' SET deleted_at = NULL WHERE id = ?', [$id]);
    }

    /** Жёстко удалить запись независимо от настройки мягкого удаления.
     * Ничего не делает если запись с указанным ID не найдена.
     * @param int $id ID записи.
     * @return void
     */
    public static function forceDelete(int $id): void
    {
        qi('DELETE FROM ' . static::$table . ' WHERE id = ?', [$id]);
    }

    /** Вернуть все записи, включая мягко удалённые.
     * Если модель не поддерживает мягкое удаление — эквивалентно findAll().
     * @return array<int, static>
     */
    public static function withTrashed(): array
    {
        if (!static::$softDelete) {
            return static::findAll();
        }
        return array_map(fn(array $row) => new static($row), q('SELECT * FROM ' . static::$table));
    }

    /** Вернуть постраничный результат.
     * Параметр $page обрезается до минимума 1; $perPage — до диапазона 1–100.
     * @param int $page     Номер страницы (минимум 1).
     * @param int $perPage  Количество записей на страницу (1–100).
     * @return array{data: array<int, static>, meta: array<string, int>}
     */
    public static function paginate(int $page = 1, int $perPage = 15): array
    {
        $page    = max(1, $page);
        $perPage = max(1, min(100, $perPage));
        $offset  = ($page - 1) * $perPage;

        $where    = static::$softDelete ? ' WHERE deleted_at IS NULL' : '';
        $total    = (int) (q1('SELECT COUNT(*) AS count FROM ' . static::$table . $where)['count'] ?? 0);
        $lastPage = max(1, (int) ceil($total / $perPage));
        // LIMIT/OFFSET вставляются как числа напрямую — PDO MySQL отвергает bound-параметры в LIMIT/OFFSET (трактует как строки).
        // Значения гарантированно целые и зажаты через max()/min(), поэтому SQL-инъекция невозможна.
        $rows     = q('SELECT * FROM ' . static::$table . $where . " LIMIT {$perPage} OFFSET {$offset}");

        return [
            'data' => array_map(fn(array $row) => new static($row), $rows),
            'meta' => [
                'total'        => $total,
                'per_page'     => $perPage,
                'current_page' => $page,
                'last_page'    => $lastPage,
            ],
        ];
    }

    /** Вставить новую запись из массива данных (фильтруется по $fillable).
     * @param array<string, mixed> $data Данные для вставки.
     * @return static|null Созданная запись, или null если обсервер отменил операцию.
     * @throws QueryException При ошибке выполнения запроса.
     */
    protected static function insert(array $data): ?static
    {
        $data = array_intersect_key($data, array_flip(static::$fillable));

        if (static::fireObserverEvent('creating', $data) === false) {
            return null;
        }

        $cols  = implode(', ', array_keys($data));
        $marks = implode(', ', array_fill(0, count($data), '?'));
        try {
            $id = qi('INSERT INTO ' . static::$table . " ({$cols}) VALUES ({$marks})", array_values($data));
        } catch (PDOException $e) {
            throw new QueryException($e->getMessage(), (string) $e->getCode(), $e);
        }

        $model = static::findById($id);
        static::fireObserverEvent('created', $model);
        return $model;
    }

    /** Обновить запись по ID непустыми полями из массива (фильтруется по $fillable).
     * Поля со значением null или '' пропускаются.
     * @param int                  $id   ID записи.
     * @param array<string, mixed> $data Данные для обновления.
     * @return static|null Обновлённая запись, или null если не найдена.
     * @throws QueryException При ошибке выполнения запроса.
     */
    protected static function modify(int $id, array $data): ?static
    {
        $data = array_filter(
            array_intersect_key($data, array_flip(static::$fillable)),
            fn($v) => $v !== null && $v !== ''
        );

        if (empty($data)) {
            return static::findById($id);
        }

        $model = static::findById($id);
        if ($model === null) {
            return null;
        }

        if (static::fireObserverEvent('updating', $model, $data) === false) {
            return $model;
        }

        $set    = implode(', ', array_map(fn($col) => "{$col} = ?", array_keys($data)));
        $values = [...array_values($data), $id];
        try {
            qi('UPDATE ' . static::$table . " SET {$set} WHERE id = ?", $values);
        } catch (PDOException $e) {
            throw new QueryException($e->getMessage(), (string) $e->getCode(), $e);
        }

        $updated       = static::findById($id);
        $changedFields = array_keys($data);
        static::fireObserverEvent('updated', $updated, $changedFields);
        return $updated;
    }
}
