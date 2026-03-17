<?php

namespace App\Console\Commands\Queue;

use App\Core\Logger;
use App\Facades\Queue;
use App\Queue\JobPayload;
use App\Abstracts\BaseCommand;

/** Воркер очереди. Обрабатывает задачи до опустошения очереди. */
class QueueWorkCommand extends BaseCommand
{
    /** @var string Имя команды. */
    public static string $name = 'queue:work';

    /** Краткое описание.
     * @return string
     */
    public function description(): string
    {
        return 'Обработать задачи из очереди.';
    }

    /** Выполнить команду.
     * @param array<int, string> $args Аргументы: --queue=name --slot=N.
     */
    public function handle(array $args): void
    {
        $queue = $this->parseOption($args, 'queue', 'default');
        $slot = (int) $this->parseOption($args, 'slot', '1');
        $workerId = gethostname() . ':' . getmypid();
        $startedAt = time();

        $lockDir = $this->ensureStorageDir();
        $lockFile = "{$lockDir}/{$queue}-{$slot}.lock";
        $heartbeatFile = "{$lockDir}/{$queue}-{$slot}.heartbeat";
        $restartFile = "{$lockDir}/restart";

        $fp = fopen($lockFile, 'c');
        if ($fp === false || !flock($fp, LOCK_EX | LOCK_NB)) {
            if ($fp !== false) {
                fclose($fp);
            }
            return; // Слот занят
        }

        try {
            $this->loop($queue, $workerId, $startedAt, $heartbeatFile, $restartFile);
        } finally {
            flock($fp, LOCK_UN);
            fclose($fp);
        }
    }

    /** Основной цикл обработки задач.
     * @param string $queue         Имя очереди.
     * @param string $workerId      Идентификатор воркера.
     * @param int    $startedAt     Timestamp старта.
     * @param string $heartbeatFile Путь к файлу heartbeat.
     * @param string $restartFile   Путь к файлу restart signal.
     */
    private function loop(
        string $queue,
        string $workerId,
        int $startedAt,
        string $heartbeatFile,
        string $restartFile,
    ): void {
        while (true) {
            if ($this->shouldRestart($restartFile, $startedAt)) {
                Logger::info("[queue:work] Restart signal received, exiting. Worker: {$workerId}");
                break;
            }

            $payload = Queue::pop($queue, $workerId);

            if ($payload === null) {
                break; // Очередь пуста
            }

            file_put_contents($heartbeatFile, (string) time());

            $this->process($payload, $queue, $workerId);
        }
    }

    /** Обработать одну задачу.
     * @param JobPayload $payload  Данные задачи.
     * @param string     $queue    Имя очереди.
     * @param string     $workerId Идентификатор воркера.
     */
    private function process(JobPayload $payload, string $queue, string $workerId): void
    {
        $job = $payload->job;
        $timeout = $job->timeout ?: (int) config('queue.timeout', 60);
        $tries = $job->tries ?: (int) config('queue.tries', 3);
        $backoff = !empty($job->backoff) ? $job->backoff : config('queue.backoff', [5, 30, 120]);

        set_time_limit($timeout);

        try {
            $job->handle();
            Queue::delete($payload->id);
            Logger::info("[queue:work] Job processed. ID: {$payload->id}, Queue: {$queue}, Worker: {$workerId}");
        } catch (\Throwable $e) {
            $this->handleFailure($payload, $e, $tries, $backoff, $queue, $workerId);
        } finally {
            set_time_limit(0);
        }
    }

    /** Обработать ошибку выполнения задачи.
     * @param JobPayload $payload  Данные задачи.
     * @param \Throwable $e        Исключение.
     * @param int        $tries    Максимум попыток.
     * @param array<int> $backoff  Задержки.
     * @param string     $queue    Имя очереди.
     * @param string     $workerId Идентификатор воркера.
     */
    private function handleFailure(
        JobPayload $payload,
        \Throwable $e,
        int $tries,
        array $backoff,
        string $queue,
        string $workerId,
    ): void {
        Logger::error("[queue:work] Job failed. ID: {$payload->id}, Queue: {$queue}, "
            . "Attempt: {$payload->attempts}/{$tries}, Error: {$e->getMessage()}");

        if ($payload->attempts < $tries) {
            $backoffIndex = min($payload->attempts - 1, count($backoff) - 1);
            $delay = $backoff[max(0, $backoffIndex)];
            Queue::release($payload->id, $delay);
        } else {
            $payload->job->failed($e);
            Queue::fail($payload->id, $e);
        }
    }

    /** Проверить наличие restart signal.
     * @param string $restartFile Путь к файлу.
     * @param int    $startedAt  Timestamp старта воркера.
     * @return bool
     */
    private function shouldRestart(string $restartFile, int $startedAt): bool
    {
        if (!file_exists($restartFile)) {
            return false;
        }

        $restartAt = (int) file_get_contents($restartFile);
        return $restartAt > $startedAt;
    }

    /** Создать storage/queue/ если не существует.
     * @return string Абсолютный путь к директории.
     */
    private function ensureStorageDir(): string
    {
        $dir = storage_path('queue');

        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        return $dir;
    }

    /** Распарсить --option=value из аргументов.
     * @param array<int, string> $args    Аргументы CLI.
     * @param string             $name    Имя опции (без --).
     * @param string             $default Значение по умолчанию.
     * @return string
     */
    private function parseOption(array $args, string $name, string $default): string
    {
        foreach ($args as $arg) {
            if (str_starts_with($arg, "--{$name}=")) {
                return substr($arg, strlen("--{$name}="));
            }
        }

        return $default;
    }
}
