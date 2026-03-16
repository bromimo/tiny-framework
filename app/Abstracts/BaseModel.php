<?php

namespace App\Abstracts;

use LogicException;
use TinyRouter\Http\Request;
use App\Core\QueryBuilder;
use App\Traits\HasObserver;
use App\Core\ModelQueryBuilder;
use App\Exceptions\QueryException;

/** Базовый класс для всех моделей.
 * Предоставляет стандартные CRUD-операции через QueryBuilder.
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

    /** Вернуть имя таблицы модели.
     * @return string
     */
    public static function getTable(): string
    {
        return static::$table;
    }

    /** Проверить, включено ли мягкое удаление.
     * @return bool
     */
    public static function hasSoftDelete(): bool
    {
        return static::$softDelete;
    }

    /** Создать ModelQueryBuilder для текущей модели.
     * @return ModelQueryBuilder
     */
    public static function query(): ModelQueryBuilder
    {
        return new ModelQueryBuilder(static::class);
    }

    /** Делегировать вызовы fluent-методов в query().
     * @param string       $method Имя метода.
     * @param array<mixed> $args   Аргументы.
     * @return mixed
     * @throws \BadMethodCallException Если метод не найден в билдере.
     */
    public static function __callStatic(string $method, array $args): mixed
    {
        $allowed = ['where', 'whereNull', 'whereNotNull', 'whereIn', 'orderBy', 'limit', 'offset'];
        if (!in_array($method, $allowed, true)) {
            throw new \BadMethodCallException("Method {$method} does not exist on " . static::class);
        }
        return static::query()->{$method}(...$args);
    }

    /** Найти одну запись по первичному ключу.
     * @param int $id ID записи.
     * @return static|null Null если запись не найдена или мягко удалена.
     */
    public static function findById(int $id): ?static
    {
        return static::query()->where('id', $id)->first();
    }

    /** Найти одну запись по произвольному полю.
     * Поле $field должно быть доверенной строкой (хардкод), не пользовательским вводом.
     * @param string $field Имя поля.
     * @param mixed  $value Искомое значение.
     * @return static|null Null если запись не найдена или мягко удалена.
     */
    public static function findByField(string $field, mixed $value): ?static
    {
        return static::query()->where($field, $value)->first();
    }

    /** Вернуть все записи таблицы. Исключает мягко удалённые если $softDelete = true.
     * @return array<int, static>
     */
    public static function findAll(): array
    {
        return static::query()->get();
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
            $result = (new QueryBuilder(static::$table))->where('id', $id)->update(['deleted_at' => date('Y-m-d H:i:s')]) > 0;
        } else {
            $result = (new QueryBuilder(static::$table))->where('id', $id)->delete() > 0;
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
        (new QueryBuilder(static::$table))->where('id', $id)->update(['deleted_at' => null]);
    }

    /** Жёстко удалить запись независимо от настройки мягкого удаления.
     * Ничего не делает если запись с указанным ID не найдена.
     * @param int $id ID записи.
     * @return void
     */
    public static function forceDelete(int $id): void
    {
        (new QueryBuilder(static::$table))->where('id', $id)->delete();
    }

    /** Вернуть все записи, включая мягко удалённые.
     * Если модель не поддерживает мягкое удаление — эквивалентно findAll().
     * @return array<int, static>
     */
    public static function withTrashed(): array
    {
        return static::query()->withTrashed()->get();
    }

    /** Вернуть постраничный результат.
     * Извлекает page и per_page из query-параметров запроса.
     * @param Request $request HTTP-запрос с query-параметрами page и per_page.
     * @return array{data: array<int, static>, meta: array<string, int>}
     */
    public static function paginate(Request $request): array
    {
        $page    = max(1, (int) ($request->query['page'] ?? 1));
        $perPage = max(1, min(100, (int) ($request->query['per_page'] ?? 15)));
        return static::query()->paginate($page, $perPage);
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

        try {
            $id = (new QueryBuilder(static::$table))->insert($data);
        } catch (\PDOException $e) {
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

        try {
            (new QueryBuilder(static::$table))->where('id', $id)->update($data);
        } catch (\PDOException $e) {
            throw new QueryException($e->getMessage(), (string) $e->getCode(), $e);
        }

        $updated       = static::findById($id);
        $changedFields = array_keys($data);
        static::fireObserverEvent('updated', $updated, $changedFields);
        return $updated;
    }
}
