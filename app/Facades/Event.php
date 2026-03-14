<?php

namespace App\Facades;

use App\Core\EventDispatcher;

/** Статический фасад над EventDispatcher. */
class Event
{
    /** @var EventDispatcher|null Единственный экземпляр диспетчера событий. */
    private static ?EventDispatcher $instance = null;

    /** Вернуть экземпляр, создав при первом обращении.
     * @return EventDispatcher
     */
    private static function instance(): EventDispatcher
    {
        if (self::$instance === null) {
            self::$instance = new EventDispatcher();
        }

        return self::$instance;
    }

    /** Установить экземпляр. Используется в bootstrap и тестах.
     * @param EventDispatcher $dispatcher Экземпляр диспетчера для установки.
     */
    public static function setInstance(EventDispatcher $dispatcher): void
    {
        self::$instance = $dispatcher;
    }

    /** Сбросить экземпляр. Используется в тестах. */
    public static function reset(): void
    {
        self::$instance = null;
    }

    /** Зарегистрировать слушателя на тип события.
     * @param string          $eventClass FQCN события.
     * @param callable|string $listener   Callable или class-string с __invoke.
     */
    public static function listen(string $eventClass, callable|string $listener): void
    {
        self::instance()->listen($eventClass, $listener);
    }

    /** Отправить событие всем зарегистрированным слушателям.
     * @param object $event Объект события.
     * @return object Тот же объект события.
     */
    public static function dispatch(object $event): object
    {
        return self::instance()->dispatch($event);
    }

    /** Проверить наличие слушателей для события.
     * @param string $eventClass FQCN события.
     * @return bool True если есть хотя бы один слушатель.
     */
    public static function hasListeners(string $eventClass): bool
    {
        return self::instance()->hasListeners($eventClass);
    }

    /** Очистить слушателей. Без аргумента — все, с аргументом — для конкретного события.
     * @param string|null $eventClass FQCN события или null для очистки всех.
     */
    public static function flush(?string $eventClass = null): void
    {
        self::instance()->flush($eventClass);
    }
}
