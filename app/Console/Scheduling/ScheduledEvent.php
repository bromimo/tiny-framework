<?php

namespace App\Console\Scheduling;

/** Описывает одно запланированное задание: команду и cron-выражение. */
class ScheduledEvent
{
    use ManagesFrequencies;

    /** @var string Cron-выражение расписания. */
    private string $expression = '* * * * *';

    /** @param string $command Имя консольной команды (например 'auth:clear-tokens'). */
    public function __construct(public readonly string $command) {}

    /** Задать произвольное cron-выражение.
     * @param string $expression Стандартное cron-выражение (5 полей).
     * @return static
     */
    public function cron(string $expression): static
    {
        $this->expression = $expression;
        return $this;
    }

    /** Получить cron-выражение расписания.
     * @return string
     */
    public function getExpression(): string
    {
        return $this->expression;
    }
}
