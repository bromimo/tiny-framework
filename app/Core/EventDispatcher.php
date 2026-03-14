<?php

namespace App\Core;

use App\Contracts\ShouldQueue;

/** Диспетчер событий. Хранит реестр слушателей и рассылает события. */
class EventDispatcher
{
    /** @var array<string, array<callable|string>> Реестр слушателей: eventClass => [listener, ...]. */
    private array $listeners = [];

    /** Зарегистрировать слушателя на тип события.
     * @param string          $eventClass FQCN события.
     * @param callable|string $listener   Callable или class-string с __invoke.
     */
    public function listen(string $eventClass, callable|string $listener): void
    {
        $this->listeners[$eventClass][] = $listener;
    }

    /** Отправить событие всем зарегистрированным слушателям.
     * @param object $event Объект события.
     * @return object Тот же объект (позволяет listeners модифицировать).
     */
    public function dispatch(object $event): object
    {
        $eventClass = get_class($event);

        if (empty($this->listeners[$eventClass])) {
            return $event;
        }

        foreach ($this->listeners[$eventClass] as $listener) {
            if (is_string($listener)) {
                $instance = new $listener();
                if ($instance instanceof ShouldQueue) {
                    Logger::info('[queued:sync] ' . $listener);
                }
                $instance->__invoke($event);
            } else {
                if (is_object($listener) && $listener instanceof ShouldQueue) {
                    Logger::info('[queued:sync] ' . get_class($listener));
                }
                ($listener)($event);
            }
        }

        return $event;
    }

    /** Проверить наличие слушателей для события.
     * @param string $eventClass FQCN события.
     * @return bool True если есть хотя бы один слушатель.
     */
    public function hasListeners(string $eventClass): bool
    {
        return !empty($this->listeners[$eventClass]);
    }

    /** Очистить слушателей. Без аргумента — все, с аргументом — для конкретного события.
     * @param string|null $eventClass FQCN события или null для очистки всех.
     */
    public function flush(?string $eventClass = null): void
    {
        if ($eventClass === null) {
            $this->listeners = [];
        } else {
            unset($this->listeners[$eventClass]);
        }
    }
}
