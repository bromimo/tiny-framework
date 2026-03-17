<?php

namespace App\Queue;

use App\Abstracts\Job;
use App\Contracts\QueueDriver;
use App\Queue\Drivers\SyncDriver;
use App\Queue\Drivers\DatabaseDriver;

/** Менеджер очередей. Управляет драйвером и делегирует операции.
 * Оборачивается фасадом Queue.
 */
class QueueManager
{
    /** @var QueueDriver Текущий драйвер. */
    private QueueDriver $driver;

    /** @param string $driverName Имя драйвера: 'sync' или 'database'. */
    public function __construct(string $driverName)
    {
        $this->driver = match ($driverName) {
            'sync'     => new SyncDriver(),
            'database' => new DatabaseDriver(),
        };
    }

    /** Добавить job в очередь.
     * @param Job $job Задача.
     */
    public function push(Job $job): void
    {
        $queue = $job->queue !== '' ? $job->queue : 'default';
        $this->driver->push($job, $queue);
    }

    /** Добавить job в очередь с задержкой.
     * @param int $delay Задержка в секундах.
     * @param Job $job   Задача.
     */
    public function later(int $delay, Job $job): void
    {
        $job->delay = $delay;
        $this->push($job);
    }

    /** Атомарно захватить следующую job.
     * @param string $queue    Имя очереди.
     * @param string $workerId Идентификатор воркера.
     * @return JobPayload|null
     */
    public function pop(string $queue, string $workerId): ?JobPayload
    {
        return $this->driver->pop($queue, $workerId);
    }

    /** Удалить job после успешного выполнения.
     * @param int $id ID записи.
     */
    public function delete(int $id): void
    {
        $this->driver->delete($id);
    }

    /** Вернуть job в очередь с задержкой.
     * @param int $id    ID записи.
     * @param int $delay Задержка в секундах.
     */
    public function release(int $id, int $delay): void
    {
        $this->driver->release($id, $delay);
    }

    /** Перенести job в failed_jobs.
     * @param int       $id ID записи.
     * @param \Throwable $e Исключение.
     */
    public function fail(int $id, \Throwable $e): void
    {
        $this->driver->fail($id, $e);
    }

    /** Количество pending jobs.
     * @param string $queue Имя очереди.
     * @return int
     */
    public function size(string $queue): int
    {
        return $this->driver->size($queue);
    }

    /** Вернуть текущий драйвер. Используется воркером для прямого доступа.
     * @return QueueDriver
     */
    public function getDriver(): QueueDriver
    {
        return $this->driver;
    }
}
