<?php

namespace App\Console\Commands\Queue;

use App\Facades\DB;
use App\Abstracts\BaseCommand;

/** Вывести список зафейленных задач. */
class QueueFailedCommand extends BaseCommand
{
    /** @var string Имя команды. */
    public static string $name = 'queue:failed';

    /** Краткое описание.
     * @return string
     */
    public function description(): string
    {
        return 'Показать список неудачных задач.';
    }

    /** Выполнить команду.
     * @param array<int, string> $args Аргументы.
     */
    public function handle(array $args): void
    {
        $rows = DB::query('SELECT id, queue, payload, exception, failed_at FROM failed_jobs ORDER BY failed_at DESC');

        if (empty($rows)) {
            echo self::GREEN . 'No failed jobs.' . self::RESET . PHP_EOL;
            return;
        }

        echo str_pad('ID', 8) . str_pad('Queue', 15) . str_pad('Job', 40) . str_pad('Error', 50) . 'Failed At' . PHP_EOL;
        echo str_repeat('-', 130) . PHP_EOL;

        foreach ($rows as $row) {
            $job = @unserialize($row['payload']);
            $jobClass = $job ? (new \ReflectionClass($job))->getShortName() : 'unknown';
            $error = strtok($row['exception'], "\n");

            echo str_pad((string) $row['id'], 8)
                . str_pad($row['queue'], 15)
                . str_pad(substr($jobClass, 0, 38), 40)
                . str_pad(substr($error, 0, 48), 50)
                . $row['failed_at'] . PHP_EOL;
        }
    }
}
