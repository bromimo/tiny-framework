<?php

namespace App\Console\Commands\Queue;

use App\Facades\DB;
use App\Core\Logger;
use App\Abstracts\BaseCommand;

/** Удалить все pending задачи из указанной очереди. */
class QueueClearCommand extends BaseCommand
{
    /** @var string Имя команды. */
    public static string $name = 'queue:clear';

    /** Краткое описание.
     * @return string
     */
    public function description(): string
    {
        return 'Очистить pending задачи из очереди (queue:clear {queue}).';
    }

    /** Выполнить команду.
     * @param array<int, string> $args Аргументы: имя очереди.
     */
    public function handle(array $args): void
    {
        $queue = $args[0] ?? null;

        if ($queue === null) {
            echo self::RED . 'Usage: queue:clear {queue}' . self::RESET . PHP_EOL;
            return;
        }

        $affected = DB::query_insert(
            'DELETE FROM jobs WHERE queue = ? AND reserved_at IS NULL',
            [$queue]
        );

        Logger::info("[queue:clear] Cleared {$affected} pending jobs from queue '{$queue}'");
        echo self::GREEN . "Cleared {$affected} pending job(s) from '{$queue}'." . self::RESET . PHP_EOL;
    }
}
