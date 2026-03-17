<?php

namespace App\Facades;

use App\Abstracts\Job;
use App\Queue\JobPayload;
use App\Queue\QueueManager;
use App\Contracts\QueueDriver;

/** Статический фасад над QueueManager. */
class Queue
{
    /** @var QueueManager|null Единственный экземпляр. */
    private static ?QueueManager $instance = null;

    /** Установить экземпляр. Вызывается в bootstrap.
     * @param QueueManager $manager
     */
    public static function setInstance(QueueManager $manager): void
    {
        self::$instance = $manager;
    }

    /** Вернуть экземпляр, создав sync-driver при первом обращении.
     * @return QueueManager
     */
    private static function instance(): QueueManager
    {
        if (self::$instance === null) {
            self::$instance = new QueueManager('sync');
        }

        return self::$instance;
    }

    /** Сбросить экземпляр. Для тестов. */
    public static function reset(): void
    {
        self::$instance = null;
    }

    /** Добавить job в очередь.
     * @param Job $job Задача.
     */
    public static function push(Job $job): void
    {
        self::instance()->push($job);
    }

    /** Добавить job в очередь с задержкой.
     * @param int $delay Задержка в секундах.
     * @param Job $job   Задача.
     */
    public static function later(int $delay, Job $job): void
    {
        self::instance()->later($delay, $job);
    }

    /** Атомарно захватить следующую job.
     * @param string $queue    Имя очереди.
     * @param string $workerId Идентификатор воркера.
     * @return JobPayload|null
     */
    public static function pop(string $queue, string $workerId): ?JobPayload
    {
        return self::instance()->pop($queue, $workerId);
    }

    /** Удалить job.
     * @param int $id ID записи.
     */
    public static function delete(int $id): void
    {
        self::instance()->delete($id);
    }

    /** Вернуть job в очередь с задержкой.
     * @param int $id    ID записи.
     * @param int $delay Задержка в секундах.
     */
    public static function release(int $id, int $delay): void
    {
        self::instance()->release($id, $delay);
    }

    /** Перенести job в failed_jobs.
     * @param int        $id ID записи.
     * @param \Throwable $e  Исключение.
     */
    public static function fail(int $id, \Throwable $e): void
    {
        self::instance()->fail($id, $e);
    }

    /** Количество pending jobs.
     * @param string $queue Имя очереди.
     * @return int
     */
    public static function size(string $queue): int
    {
        return self::instance()->size($queue);
    }

    /** Вернуть текущий драйвер.
     * @return QueueDriver
     */
    public static function getDriver(): QueueDriver
    {
        return self::instance()->getDriver();
    }
}
