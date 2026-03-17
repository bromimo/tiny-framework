<?php

namespace App\Contracts;

use Throwable;
use App\Abstracts\Job;
use App\Queue\JobPayload;

/** Контракт драйвера очередей. */
interface QueueDriver
{
    /** Добавить job в очередь.
     * @param Job    $job   Задача для выполнения.
     * @param string $queue Имя очереди.
     */
    public function push(Job $job, string $queue): void;

    /** Атомарно захватить следующую доступную job из очереди.
     * @param string $queue    Имя очереди.
     * @param string $workerId Идентификатор воркера (hostname:pid).
     * @return JobPayload|null Null если очередь пуста.
     */
    public function pop(string $queue, string $workerId): ?JobPayload;

    /** Удалить job после успешного выполнения.
     * @param int $id ID записи в таблице jobs.
     */
    public function delete(int $id): void;

    /** Вернуть job в очередь с задержкой (для retry с backoff).
     * @param int $id    ID записи в таблице jobs.
     * @param int $delay Задержка в секундах.
     */
    public function release(int $id, int $delay): void;

    /** Перенести job в failed_jobs.
     * @param int       $id ID записи в таблице jobs.
     * @param Throwable $e  Исключение.
     */
    public function fail(int $id, Throwable $e): void;

    /** Количество pending jobs в очереди.
     * @param string $queue Имя очереди.
     * @return int
     */
    public function size(string $queue): int;
}
