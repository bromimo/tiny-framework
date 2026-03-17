<?php

namespace App\Abstracts;

use Throwable;

/** Базовый класс асинхронной задачи.
 * Наследники реализуют handle() с бизнес-логикой.
 * Свойства timeout/tries/backoff/queue/delay переопределяют дефолты из config/queue.php.
 *
 * Ограничения сериализации: свойства должны быть сериализуемыми (скаляры, массивы, объекты без ресурсов).
 * Не храните PDO, file handles, замыкания. Храните ID и загружайте объекты в handle().
 */
abstract class Job
{
    /** @var int Таймаут в секундах; 0 = из конфига. */
    public int $timeout = 0;

    /** @var int Максимальное количество попыток; 0 = из конфига. */
    public int $tries = 0;

    /** @var array<int> Задержки между попытками в секундах; [] = из конфига. */
    public array $backoff = [];

    /** @var string Имя очереди; '' = из конфига. */
    public string $queue = '';

    /** @var int Задержка перед первым выполнением в секундах. */
    public int $delay = 0;

    /** Выполнить задачу. */
    abstract public function handle(): void;

    /** Хук при окончательном fail (все попытки исчерпаны).
     * @param Throwable $e Последнее исключение.
     */
    public function failed(Throwable $e): void {}
}
