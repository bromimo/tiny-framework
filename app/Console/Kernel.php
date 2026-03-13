<?php

namespace App\Console;

use App\Abstracts\BaseKernel;
use App\Console\Scheduling\Schedule;

/** Консольное ядро приложения. Регистрирует команды и расписание задач. */
class Kernel extends BaseKernel
{
    /** Зарегистрировать консольные команды. */
    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');

        $kernel = $this;
        require base_path('routes/console.php');
    }

    /** Определить расписание периодических задач.
     * @param Schedule $schedule
     */
    protected function schedule(Schedule $schedule): void
    {
        $schedule->command('auth:clear-tokens')->daily();
    }
}
