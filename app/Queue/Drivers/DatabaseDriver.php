<?php

namespace App\Queue\Drivers;

use Throwable;
use App\Facades\DB;
use App\Abstracts\Job;
use App\Queue\JobPayload;
use App\Contracts\QueueDriver;

/** Database-драйвер очередей. Хранит задачи в таблице jobs. */
class DatabaseDriver implements QueueDriver
{
    /** Добавить job в очередь (INSERT в таблицу jobs).
     * @param Job    $job   Задача.
     * @param string $queue Имя очереди.
     */
    public function push(Job $job, string $queue): void
    {
        $delay = $job->delay;
        $availableAt = $delay > 0
            ? date('Y-m-d H:i:s', time() + $delay)
            : date('Y-m-d H:i:s');

        DB::query_insert(
            'INSERT INTO jobs (queue, payload, available_at, created_at) VALUES (?, ?, ?, NOW())',
            [$queue, serialize($job), $availableAt]
        );
    }

    /** Атомарно захватить следующую job из очереди.
     * @param string $queue    Имя очереди.
     * @param string $workerId Идентификатор воркера.
     * @return JobPayload|null Null если очередь пуста.
     */
    public function pop(string $queue, string $workerId): ?JobPayload
    {
        return DB::transaction(function () use ($queue, $workerId) {
            $affected = DB::query_insert(
                'UPDATE jobs SET reserved_at = NOW(), worker_id = ?, attempts = attempts + 1
                 WHERE queue = ? AND available_at <= NOW() AND reserved_at IS NULL
                 ORDER BY id ASC LIMIT 1',
                [$workerId, $queue]
            );

            if ($affected === 0) {
                return null;
            }

            $row = DB::query_once(
                'SELECT * FROM jobs WHERE worker_id = ? AND queue = ? AND reserved_at IS NOT NULL
                 ORDER BY id ASC LIMIT 1',
                [$workerId, $queue]
            );

            if ($row === null) {
                return null;
            }

            return new JobPayload(
                id: (int) $row['id'],
                queue: $row['queue'],
                job: unserialize($row['payload']),
                attempts: (int) $row['attempts'],
            );
        });
    }

    /** Удалить job после успешного выполнения.
     * @param int $id ID записи.
     */
    public function delete(int $id): void
    {
        DB::query_insert('DELETE FROM jobs WHERE id = ?', [$id]);
    }

    /** Вернуть job в очередь с задержкой (retry с backoff).
     * @param int $id    ID записи.
     * @param int $delay Задержка в секундах.
     */
    public function release(int $id, int $delay): void
    {
        $availableAt = date('Y-m-d H:i:s', time() + $delay);
        DB::query_insert(
            'UPDATE jobs SET reserved_at = NULL, worker_id = NULL, available_at = ? WHERE id = ?',
            [$availableAt, $id]
        );
    }

    /** Перенести job в failed_jobs и удалить из jobs.
     * @param int       $id ID записи.
     * @param Throwable $e  Исключение.
     */
    public function fail(int $id, Throwable $e): void
    {
        $row = DB::query_once('SELECT * FROM jobs WHERE id = ?', [$id]);

        if ($row === null) {
            return;
        }

        $exception = $e->getMessage() . "\n" . $e->getTraceAsString();

        DB::transaction(function () use ($row, $exception, $id) {
            DB::query_insert(
                'INSERT INTO failed_jobs (queue, payload, exception, created_at, failed_at)
                 VALUES (?, ?, ?, ?, NOW())',
                [$row['queue'], $row['payload'], $exception, $row['created_at']]
            );
            DB::query_insert('DELETE FROM jobs WHERE id = ?', [$id]);
        });
    }

    /** Количество pending jobs в очереди.
     * @param string $queue Имя очереди.
     * @return int
     */
    public function size(string $queue): int
    {
        $row = DB::query_once(
            'SELECT COUNT(*) as cnt FROM jobs WHERE queue = ? AND reserved_at IS NULL',
            [$queue]
        );

        return (int) ($row['cnt'] ?? 0);
    }
}
