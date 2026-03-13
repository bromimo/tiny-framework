<?php

namespace Tests\Unit\Console;

use PHPUnit\Framework\TestCase;
use App\Console\Scheduling\Schedule;
use App\Console\Scheduling\DayOfWeek;
use App\Console\Scheduling\ScheduledEvent;

/** Тесты генерации cron-выражений планировщика задач. */
class SchedulingTest extends TestCase
{
    private ScheduledEvent $event;

    /** Создаёт свежий ScheduledEvent перед каждым тестом. */
    protected function setUp(): void
    {
        parent::setUp();
        $this->event = new ScheduledEvent('test:command');
    }

    // -------------------------------------------------------------------------
    // Минуты
    // -------------------------------------------------------------------------

    /** Проверяет выражение для запуска каждую минуту. */
    public function test_every_minute(): void
    {
        $this->assertSame('* * * * *', $this->event->everyMinute()->getExpression());
    }

    /** Проверяет выражение для запуска каждые 5 минут. */
    public function test_every_five_minutes(): void
    {
        $this->assertSame('*/5 * * * *', $this->event->everyFiveMinutes()->getExpression());
    }

    /** Проверяет выражение для запуска каждые 10 минут. */
    public function test_every_ten_minutes(): void
    {
        $this->assertSame('*/10 * * * *', $this->event->everyTenMinutes()->getExpression());
    }

    /** Проверяет выражение для запуска каждые 15 минут. */
    public function test_every_fifteen_minutes(): void
    {
        $this->assertSame('*/15 * * * *', $this->event->everyFifteenMinutes()->getExpression());
    }

    /** Проверяет выражение для запуска каждые 30 минут. */
    public function test_every_thirty_minutes(): void
    {
        $this->assertSame('0,30 * * * *', $this->event->everyThirtyMinutes()->getExpression());
    }

    // -------------------------------------------------------------------------
    // Часы
    // -------------------------------------------------------------------------

    /** Проверяет выражение для ежечасного запуска. */
    public function test_hourly(): void
    {
        $this->assertSame('0 * * * *', $this->event->hourly()->getExpression());
    }

    /** Проверяет выражение для ежечасного запуска в указанную минуту. */
    public function test_hourly_at(): void
    {
        $this->assertSame('15 * * * *', $this->event->hourlyAt(15)->getExpression());
    }

    /** Проверяет выражение для запуска каждые 2 часа. */
    public function test_every_two_hours(): void
    {
        $this->assertSame('0 */2 * * *', $this->event->everyTwoHours()->getExpression());
    }

    /** Проверяет выражение для запуска каждые 3 часа. */
    public function test_every_three_hours(): void
    {
        $this->assertSame('0 */3 * * *', $this->event->everyThreeHours()->getExpression());
    }

    /** Проверяет выражение для запуска каждые 6 часов. */
    public function test_every_six_hours(): void
    {
        $this->assertSame('0 */6 * * *', $this->event->everySixHours()->getExpression());
    }

    // -------------------------------------------------------------------------
    // Дни
    // -------------------------------------------------------------------------

    /** Проверяет выражение для ежедневного запуска в полночь. */
    public function test_daily(): void
    {
        $this->assertSame('0 0 * * *', $this->event->daily()->getExpression());
    }

    /** Проверяет выражение для ежедневного запуска в указанное время. */
    public function test_daily_at(): void
    {
        $this->assertSame('30 10 * * *', $this->event->dailyAt('10:30')->getExpression());
    }

    /** Проверяет выражение для запуска дважды в день. */
    public function test_twice_daily(): void
    {
        $this->assertSame('0 1,13 * * *', $this->event->twiceDaily()->getExpression());
    }

    /** Проверяет выражение для запуска дважды в день в указанную минуту. */
    public function test_twice_daily_at(): void
    {
        $this->assertSame('15 6,18 * * *', $this->event->twiceDailyAt(6, 18, 15)->getExpression());
    }

    // -------------------------------------------------------------------------
    // Дни недели
    // -------------------------------------------------------------------------

    /** Проверяет, что weekdays() выставляет позицию 5 в '1,5'. */
    public function test_weekdays(): void
    {
        $expression = $this->event->weekdays()->getExpression();
        $parts = preg_split('/\s+/', $expression);
        $this->assertSame('1,5', $parts[4]);
    }

    /** Проверяет, что weekends() выставляет позицию 5 в '6,0'. */
    public function test_weekends(): void
    {
        $expression = $this->event->weekends()->getExpression();
        $parts = preg_split('/\s+/', $expression);
        $this->assertSame('6,0', $parts[4]);
    }

    /** Проверяет, что mondays() выставляет позицию 5 в '1'. */
    public function test_mondays(): void
    {
        $expression = $this->event->mondays()->getExpression();
        $parts = preg_split('/\s+/', $expression);
        $this->assertSame('1', $parts[4]);
    }

    /** Проверяет, что sundays() выставляет позицию 5 в '0'. */
    public function test_sundays(): void
    {
        $expression = $this->event->sundays()->getExpression();
        $parts = preg_split('/\s+/', $expression);
        $this->assertSame('0', $parts[4]);
    }

    // -------------------------------------------------------------------------
    // Недели
    // -------------------------------------------------------------------------

    /** Проверяет выражение для еженедельного запуска в воскресенье в полночь. */
    public function test_weekly(): void
    {
        $this->assertSame('0 0 * * 0', $this->event->weekly()->getExpression());
    }

    /** Проверяет выражение для еженедельного запуска в указанный день и время. */
    public function test_weekly_on(): void
    {
        $this->assertSame('0 8 * * 3', $this->event->weeklyOn(DayOfWeek::Wednesday, '8:0')->getExpression());
    }

    // -------------------------------------------------------------------------
    // Месяцы
    // -------------------------------------------------------------------------

    /** Проверяет выражение для ежемесячного запуска 1-го числа в полночь. */
    public function test_monthly(): void
    {
        $this->assertSame('0 0 1 * *', $this->event->monthly()->getExpression());
    }

    /** Проверяет выражение для ежемесячного запуска в указанный день и время. */
    public function test_monthly_on(): void
    {
        $this->assertSame('0 9 15 * *', $this->event->monthlyOn(15, '9:0')->getExpression());
    }

    /** Проверяет выражение для запуска дважды в месяц. */
    public function test_twice_monthly(): void
    {
        $this->assertSame('0 0 1,16 * *', $this->event->twiceMonthly()->getExpression());
    }

    // -------------------------------------------------------------------------
    // Кварталы / Год
    // -------------------------------------------------------------------------

    /** Проверяет выражение для ежеквартального запуска. */
    public function test_quarterly(): void
    {
        $this->assertSame('0 0 1 1-12/3 *', $this->event->quarterly()->getExpression());
    }

    /** Проверяет выражение для ежегодного запуска 1 января в полночь. */
    public function test_yearly(): void
    {
        $this->assertSame('0 0 1 1 *', $this->event->yearly()->getExpression());
    }

    /** Проверяет выражение для ежегодного запуска в указанный месяц, день и время. */
    public function test_yearly_on(): void
    {
        $this->assertSame('30 14 25 12 *', $this->event->yearlyOn(12, 25, '14:30')->getExpression());
    }

    // -------------------------------------------------------------------------
    // Прочее
    // -------------------------------------------------------------------------

    /** Проверяет задание произвольного cron-выражения. */
    public function test_custom_cron(): void
    {
        $this->assertSame('5 4 * * 0', $this->event->cron('5 4 * * 0')->getExpression());
    }

    /** Проверяет регистрацию нескольких событий в Schedule. */
    public function test_schedule_registers_events(): void
    {
        $schedule = new Schedule();
        $e1 = $schedule->command('cache:clear')->daily();
        $e2 = $schedule->command('auth:clear-tokens')->hourly();

        $events = $schedule->events();

        $this->assertCount(2, $events);
        $this->assertSame('0 0 * * *', $e1->getExpression());
        $this->assertSame('0 * * * *', $e2->getExpression());
    }

    /** Проверяет цепочку daily()->weekdays() → '0 0 * * 1,5'. */
    public function test_chaining_daily_and_weekdays(): void
    {
        $this->assertSame('0 0 * * 1,5', $this->event->daily()->weekdays()->getExpression());
    }
}
