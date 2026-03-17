<?php

namespace App\Console\Commands\Queue;

use App\Facades\DB;
use App\Core\Logger;
use App\Abstracts\BaseCommand;

/** Повторить выполнение зафейленных задач. */
class QueueRetryCommand extends BaseCommand
{
    /** @var string Имя команды. */
    public static string $name = 'queue:retry';

    /** Краткое описание.
     * @return string
     */
    public function description(): string
    {
        return 'Повторить неудачные задачи (queue:retry {id|all}).';
    }

    /** Выполнить команду.
     * @param array<int, string> $args Аргументы: id или 'all'.
     */
    public function handle(array $args): void
    {
        $target = $args[0] ?? null;

        if ($target === null) {
            echo self::RED . 'Usage: queue:retry {id|all}' . self::RESET . PHP_EOL;
            return;
        }

        if ($target === 'all') {
            $this->retryAll();
        } else {
            $this->retryOne((int) $target);
        }
    }

    /** Повторить одну failed job.
     * @param int $id ID в failed_jobs.
     */
    private function retryOne(int $id): void
    {
        $row = DB::query_once('SELECT * FROM failed_jobs WHERE id = ?', [$id]);

        if ($row === null) {
            echo self::RED . "Failed job #{$id} not found." . self::RESET . PHP_EOL;
            return;
        }

        DB::transaction(function () use ($row) {
            DB::query_insert(
                'INSERT INTO jobs (queue, payload, attempts, available_at, created_at) VALUES (?, ?, 0, NOW(), NOW())',
                [$row['queue'], $row['payload']]
            );
            DB::query_insert('DELETE FROM failed_jobs WHERE id = ?', [$row['id']]);
        });

        Logger::info("[queue:retry] Retried failed job #{$id}");
        echo self::GREEN . "Job #{$id} moved back to queue." . self::RESET . PHP_EOL;
    }

    /** Повторить все failed jobs. */
    private function retryAll(): void
    {
        $rows = DB::query('SELECT * FROM failed_jobs');

        if (empty($rows)) {
            echo self::GREEN . 'No failed jobs to retry.' . self::RESET . PHP_EOL;
            return;
        }

        $count = 0;
        foreach ($rows as $row) {
            DB::transaction(function () use ($row) {
                DB::query_insert(
                    'INSERT INTO jobs (queue, payload, attempts, available_at, created_at) VALUES (?, ?, 0, NOW(), NOW())',
                    [$row['queue'], $row['payload']]
                );
                DB::query_insert('DELETE FROM failed_jobs WHERE id = ?', [$row['id']]);
            });
            $count++;
        }

        Logger::info("[queue:retry] Retried all {$count} failed jobs");
        echo self::GREEN . "Retried {$count} job(s)." . self::RESET . PHP_EOL;
    }
}
