<?php

namespace App\Traits;

use ReflectionClass;
use App\Attributes\ObservedBy;

/** Добавляет поддержку обсерверов для модели.
 * Автоматически регистрирует обсервер из атрибута #[ObservedBy] при первом CRUD-вызове.
 */
trait HasObserver
{
    /** @var array<string, object|null> Реестр обсерверов: modelClass => observerInstance|null. */
    private static array $observers = [];

    /** Вернуть обсервер для текущей модели (lazy init из атрибута #[ObservedBy]).
     * @return object|null Экземпляр обсервера или null.
     */
    private static function resolveObserver(): ?object
    {
        if (array_key_exists(static::class, self::$observers)) {
            return self::$observers[static::class];
        }

        $ref   = new ReflectionClass(static::class);
        $attrs = $ref->getAttributes(ObservedBy::class);

        if (empty($attrs)) {
            self::$observers[static::class] = null;
            return null;
        }

        $instance = new ($attrs[0]->newInstance()->observerClass)();
        self::$observers[static::class] = $instance;
        return $instance;
    }

    /** Зарегистрировать обсервер для модели вручную. Перезаписывает авто-регистрацию.
     * @param string $observerClass FQCN класса обсервера.
     */
    public static function observe(string $observerClass): void
    {
        self::$observers[static::class] = new $observerClass();
    }

    /** Снять обсервер с модели. Используется в тестах. */
    public static function removeObserver(): void
    {
        unset(self::$observers[static::class]);
    }

    /** Вызвать метод обсервера, если зарегистрирован.
     * @param string $event   Имя хука (creating, created, и т.д.).
     * @param mixed  ...$args Аргументы для передачи в хук.
     * @return bool False если обсервер вернул false (отмена операции), иначе true.
     */
    protected static function fireObserverEvent(string $event, mixed &...$args): bool
    {
        $observer = static::resolveObserver();
        if ($observer === null || !method_exists($observer, $event)) {
            return true;
        }

        $result = $observer->$event(...$args);
        return $result !== false;
    }
}
