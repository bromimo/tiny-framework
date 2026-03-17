<?php

namespace App\Queue;

use App\Abstracts\Job;

/** DTO с данными захваченной job из очереди. */
readonly class JobPayload
{
    /** @param int    $id       ID записи в таблице jobs.
     *  @param string $queue    Имя очереди.
     *  @param Job    $job      Десериализованный объект задачи.
     *  @param int    $attempts Количество попыток.
     */
    public function __construct(
        public int $id,
        public string $queue,
        public Job $job,
        public int $attempts,
    ) {}
}
