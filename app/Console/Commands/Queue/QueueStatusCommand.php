<?php

namespace App\Console\Commands\Queue;

use App\Facades\DB;
use App\Abstracts\BaseCommand;

/** Показать статус очередей: pending/reserved/failed jobs, heartbeat воркеров. */
class QueueStatusCommand extends BaseCommand
{
    /** @var string Имя команды. */
    public static string $name = 'queue:status';

    /** Краткое описание.
     * @return string
     */
    public function description(): string
    {
        return 'Показать статус очередей и воркеров.';
    }

    /** Выполнить команду.
     * @param array<int, string> $args Аргументы.
     */
    public function handle(array $args): void
    {
        $queues = config('queue.queues', ['default' => ['workers' => 1]]);
        $lockDir = storage_path('queue');

        foreach ($queues as $queueName => $queueConfig) {
            $workers = $queueConfig['workers'] ?? 1;

            $pending = DB::query_once(
                'SELECT COUNT(*) as cnt FROM jobs WHERE queue = ? AND reserved_at IS NULL',
                [$queueName]
            );
            $reserved = DB::query_once(
                'SELECT COUNT(*) as cnt FROM jobs WHERE queue = ? AND reserved_at IS NOT NULL',
                [$queueName]
            );
            $failed = DB::query_once(
                'SELECT COUNT(*) as cnt FROM failed_jobs WHERE queue = ?',
                [$queueName]
            );

            echo self::YELLOW . "Queue: {$queueName}" . self::RESET
                . " ({$workers} workers)" . PHP_EOL;
            echo "  Pending: " . self::GREEN . ($pending['cnt'] ?? 0) . self::RESET
                . " | Reserved: " . self::YELLOW . ($reserved['cnt'] ?? 0) . self::RESET
                . " | Failed: " . self::RED . ($failed['cnt'] ?? 0) . self::RESET . PHP_EOL;

            for ($slot = 1; $slot <= $workers; $slot++) {
                $heartbeatFile = "{$lockDir}/{$queueName}-{$slot}.heartbeat";
                $lockFile = "{$lockDir}/{$queueName}-{$slot}.lock";

                $status = $this->getWorkerStatus($lockFile, $heartbeatFile);
                echo "  Worker {$queueName}:{$slot} — {$status}" . PHP_EOL;
            }

            echo PHP_EOL;
        }
    }

    /** Определить статус воркера по lock и heartbeat файлам.
     * @param string $lockFile      Путь к lock-файлу.
     * @param string $heartbeatFile Путь к heartbeat-файлу.
     * @return string Описание статуса.
     */
    private function getWorkerStatus(string $lockFile, string $heartbeatFile): string
    {
        if (!file_exists($lockFile)) {
            return self::GRAY . 'idle (no lock)' . self::RESET;
        }

        $fp = fopen($lockFile, 'c');
        if ($fp === false) {
            return self::GRAY . 'unknown' . self::RESET;
        }

        if (flock($fp, LOCK_EX | LOCK_NB)) {
            flock($fp, LOCK_UN);
            fclose($fp);
            return self::GRAY . 'idle (no lock)' . self::RESET;
        }

        fclose($fp);

        if (file_exists($heartbeatFile)) {
            $lastBeat = (int) file_get_contents($heartbeatFile);
            $ago = time() - $lastBeat;
            return self::GREEN . "alive (heartbeat {$ago}s ago)" . self::RESET;
        }

        return self::YELLOW . 'running (no heartbeat yet)' . self::RESET;
    }
}
