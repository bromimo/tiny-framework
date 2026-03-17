<?php

namespace App\Queue\Drivers;

use Throwable;
use App\Abstracts\Job;
use App\Queue\JobPayload;
use App\Contracts\QueueDriver;

/** Синхронный драйвер — выполняет задачу сразу в текущем процессе. Для dev/тестов. */
class SyncDriver implements QueueDriver
{
    /** Выполнить job немедленно. Прогоняет через serialize/unserialize для раннего обнаружения проблем сериализации.
     * @param Job    $job   Задача.
     * @param string $queue Имя очереди (игнорируется).
     * @throws Throwable При ошибке выполнения или сериализации.
     */
    public function push(Job $job, string $queue): void
    {
        /** @var Job $job */
        $job = unserialize(serialize($job));
        $job->handle();
    }

    /** Всегда null — sync-драйвер не хранит jobs.
     * @param string $queue    Имя очереди.
     * @param string $workerId Идентификатор воркера.
     * @return JobPayload|null
     */
    public function pop(string $queue, string $workerId): ?JobPayload
    {
        return null;
    }

    /** No-op.
     * @param int $id ID job.
     */
    public function delete(int $id): void {}

    /** No-op.
     * @param int $id    ID job.
     * @param int $delay Задержка.
     */
    public function release(int $id, int $delay): void {}

    /** No-op.
     * @param int       $id ID job.
     * @param Throwable $e  Исключение.
     */
    public function fail(int $id, Throwable $e): void {}

    /** Всегда 0 — sync-драйвер не хранит jobs.
     * @param string $queue Имя очереди.
     * @return int
     */
    public function size(string $queue): int
    {
        return 0;
    }
}
