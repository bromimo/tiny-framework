<?php

namespace App\Console\Commands\Queue;

use App\Facades\DB;
use App\Core\Logger;
use App\Abstracts\BaseCommand;

/** Entry point для cron. Stale recovery + запуск недостающих воркеров. */
class QueueStartCommand extends BaseCommand
{
    /** @var string Имя команды. */
    public static string $name = 'queue:start';

    /** Краткое описание.
     * @return string
     */
    public function description(): string
    {
        return 'Запустить воркеры очередей (entry point для cron).';
    }

    /** Выполнить команду.
     * @param array<int, string> $args Аргументы (не используются).
     */
    public function handle(array $args): void
    {
        $queues = config('queue.queues', ['default' => ['workers' => 1]]);
        $lockDir = $this->ensureStorageDir();

        $this->recoverStaleJobs();

        foreach ($queues as $queueName => $queueConfig) {
            $workers = $queueConfig['workers'] ?? 1;

            for ($slot = 1; $slot <= $workers; $slot++) {
                $this->startWorkerIfFree($queueName, $slot, $lockDir);
            }
        }
    }

    /** Запустить воркер если слот свободен.
     * @param string $queue   Имя очереди.
     * @param int    $slot    Номер слота.
     * @param string $lockDir Путь к директории lock-файлов.
     */
    private function startWorkerIfFree(string $queue, int $slot, string $lockDir): void
    {
        $lockFile = "{$lockDir}/{$queue}-{$slot}.lock";

        $fp = fopen($lockFile, 'c');
        if ($fp === false) {
            return;
        }

        if (flock($fp, LOCK_EX | LOCK_NB)) {
            flock($fp, LOCK_UN);
            fclose($fp);

            $phpBinary = PHP_BINARY;
            $runScript = base_path('run');
            $cmd = "\"{$phpBinary}\" \"{$runScript}\" queue:work --queue={$queue} --slot={$slot}";

            if (PHP_OS_FAMILY === 'Windows') {
                pclose(popen("start /B {$cmd}", 'r'));
            } else {
                pclose(popen("{$cmd} > /dev/null 2>&1 &", 'r'));
            }

            Logger::info("[queue:start] Started worker. Queue: {$queue}, Slot: {$slot}");
        } else {
            fclose($fp);
        }
    }

    /** Восстановить зависшие задачи (stale job recovery).
     * Job считается зависшей если reserved_at старше per-job timeout * 2.
     */
    private function recoverStaleJobs(): void
    {
        $defaultTimeout = (int) config('queue.timeout', 60);
        $defaultTries = (int) config('queue.tries', 3);
        $defaultBackoff = config('queue.backoff', [5, 30, 120]);

        $staleJobs = DB::query(
            'SELECT * FROM jobs WHERE reserved_at IS NOT NULL'
        );

        foreach ($staleJobs as $row) {
            $job = @unserialize($row['payload']);
            $jobTimeout = ($job instanceof \App\Abstracts\Job && $job->timeout > 0) ? $job->timeout : $defaultTimeout;
            $threshold = $jobTimeout * 2;
            $reservedAt = strtotime($row['reserved_at']);

            if (time() - $reservedAt < $threshold) {
                continue;
            }

            $tries = ($job instanceof \App\Abstracts\Job && $job->tries > 0) ? $job->tries : $defaultTries;
            $backoff = ($job instanceof \App\Abstracts\Job && !empty($job->backoff)) ? $job->backoff : $defaultBackoff;
            $attempts = (int) $row['attempts'];

            if ($attempts < $tries) {
                $backoffIndex = min($attempts - 1, count($backoff) - 1);
                $delay = $backoff[max(0, $backoffIndex)];
                $availableAt = date('Y-m-d H:i:s', time() + $delay);

                DB::query_insert(
                    'UPDATE jobs SET reserved_at = NULL, worker_id = NULL, available_at = ? WHERE id = ?',
                    [$availableAt, $row['id']]
                );

                Logger::info("[queue:start] Recovered stale job. ID: {$row['id']}, Queue: {$row['queue']}");
            } else {
                $exception = 'Worker died, job timed out after ' . $attempts . ' attempts';

                DB::transaction(function () use ($row, $exception) {
                    DB::query_insert(
                        'INSERT INTO failed_jobs (queue, payload, exception, created_at, failed_at) VALUES (?, ?, ?, ?, NOW())',
                        [$row['queue'], $row['payload'], $exception, $row['created_at']]
                    );
                    DB::query_insert('DELETE FROM jobs WHERE id = ?', [$row['id']]);
                });

                Logger::info("[queue:start] Moved stale job to failed_jobs. ID: {$row['id']}, Queue: {$row['queue']}");
            }
        }
    }

    /** Создать storage/queue/ если не существует.
     * @return string
     */
    private function ensureStorageDir(): string
    {
        $dir = storage_path('queue');

        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        return $dir;
    }
}
