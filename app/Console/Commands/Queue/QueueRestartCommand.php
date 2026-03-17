<?php

namespace App\Console\Commands\Queue;

use App\Core\Logger;
use App\Abstracts\BaseCommand;

/** Отправить сигнал перезапуска воркерам. */
class QueueRestartCommand extends BaseCommand
{
    /** @var string Имя команды. */
    public static string $name = 'queue:restart';

    /** Краткое описание.
     * @return string
     */
    public function description(): string
    {
        return 'Перезапустить воркеры (graceful restart через signal).';
    }

    /** Выполнить команду.
     * @param array<int, string> $args Аргументы.
     */
    public function handle(array $args): void
    {
        $dir = storage_path('queue');

        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        file_put_contents("{$dir}/restart", (string) time());

        Logger::info('[queue:restart] Restart signal sent');
        echo self::GREEN . 'Restart signal sent. Workers will restart after current job.' . self::RESET . PHP_EOL;
    }
}
