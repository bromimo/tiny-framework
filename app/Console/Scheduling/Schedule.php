<?php

namespace App\Console\Scheduling;

/** Реестр запланированных задач. Заполняется в Kernel::schedule(). */
class Schedule
{
    /** @var list<ScheduledEvent> */
    private array $events = [];

    /** Зарегистрировать команду в расписании.
     * @param string $command Имя консольной команды (например 'auth:clear-tokens').
     * @return ScheduledEvent Объект события для настройки расписания.
     */
    public function command(string $command): ScheduledEvent
    {
        $event          = new ScheduledEvent($command);
        $this->events[] = $event;
        return $event;
    }

    /** Получить все зарегистрированные события.
     * @return list<ScheduledEvent>
     */
    public function events(): array
    {
        return $this->events;
    }
}
