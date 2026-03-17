<?php

namespace App\Console\Commands\Queue;

use App\Facades\DB;
use App\Core\Logger;
use App\Abstracts\BaseCommand;

/** Очистить таблицу failed_jobs. */
class QueueFlushCommand extends BaseCommand
{
    /** @var string Имя команды. */
    public static string $name = 'queue:flush';

    /** Краткое описание.
     * @return string
     */
    public function description(): string
    {
        return 'Очистить таблицу неудачных задач.';
    }

    /** Выполнить команду.
     * @param array<int, string> $args Аргументы.
     */
    public function handle(array $args): void
    {
        $affected = DB::query_insert('DELETE FROM failed_jobs');
        Logger::info("[queue:flush] Cleared {$affected} failed jobs");
        echo self::GREEN . "Cleared {$affected} failed job(s)." . self::RESET . PHP_EOL;
    }
}
