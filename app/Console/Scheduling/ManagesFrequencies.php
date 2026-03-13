<?php

namespace App\Console\Scheduling;

/** Методы настройки частоты запуска задачи. Используется в ScheduledEvent. */
trait ManagesFrequencies
{
    // -------------------------------------------------------------------------
    // Минуты
    // -------------------------------------------------------------------------

    /** Запускать каждую минуту. */
    public function everyMinute(): static
    {
        return $this->spliceIntoPosition(1, '*');
    }

    /** Запускать каждые 2 минуты. */
    public function everyTwoMinutes(): static
    {
        return $this->spliceIntoPosition(1, '*/2');
    }

    /** Запускать каждые 3 минуты. */
    public function everyThreeMinutes(): static
    {
        return $this->spliceIntoPosition(1, '*/3');
    }

    /** Запускать каждые 4 минуты. */
    public function everyFourMinutes(): static
    {
        return $this->spliceIntoPosition(1, '*/4');
    }

    /** Запускать каждые 5 минут. */
    public function everyFiveMinutes(): static
    {
        return $this->spliceIntoPosition(1, '*/5');
    }

    /** Запускать каждые 10 минут. */
    public function everyTenMinutes(): static
    {
        return $this->spliceIntoPosition(1, '*/10');
    }

    /** Запускать каждые 15 минут. */
    public function everyFifteenMinutes(): static
    {
        return $this->spliceIntoPosition(1, '*/15');
    }

    /** Запускать каждые 30 минут. */
    public function everyThirtyMinutes(): static
    {
        return $this->spliceIntoPosition(1, '0,30');
    }

    // -------------------------------------------------------------------------
    // Часы
    // -------------------------------------------------------------------------

    /** Запускать ежечасно (в 0 минут). */
    public function hourly(): static
    {
        return $this->spliceIntoPosition(1, 0);
    }

    /** Запускать ежечасно в указанную минуту.
     * @param array|string|int $offset Минута(ы) внутри часа.
     */
    public function hourlyAt(array|string|int $offset): static
    {
        return $this->hourBasedSchedule($offset, '*');
    }

    /** Запускать каждый нечётный час. */
    public function everyOddHour(array|string|int $offset = 0): static
    {
        return $this->hourBasedSchedule($offset, '1-23/2');
    }

    /** Запускать каждые 2 часа. */
    public function everyTwoHours(array|string|int $offset = 0): static
    {
        return $this->hourBasedSchedule($offset, '*/2');
    }

    /** Запускать каждые 3 часа. */
    public function everyThreeHours(array|string|int $offset = 0): static
    {
        return $this->hourBasedSchedule($offset, '*/3');
    }

    /** Запускать каждые 4 часа. */
    public function everyFourHours(array|string|int $offset = 0): static
    {
        return $this->hourBasedSchedule($offset, '*/4');
    }

    /** Запускать каждые 6 часов. */
    public function everySixHours(array|string|int $offset = 0): static
    {
        return $this->hourBasedSchedule($offset, '*/6');
    }

    // -------------------------------------------------------------------------
    // Дни
    // -------------------------------------------------------------------------

    /** Запускать ежедневно в полночь. */
    public function daily(): static
    {
        return $this->hourBasedSchedule(0, 0);
    }

    /** Псевдоним для dailyAt().
     * @param string $time Время в формате 'HH:MM'.
     */
    public function at(string $time): static
    {
        return $this->dailyAt($time);
    }

    /** Запускать ежедневно в указанное время.
     * @param string $time Время в формате 'HH:MM' (например '10:30').
     */
    public function dailyAt(string $time): static
    {
        $segments = explode(':', $time);

        return $this->hourBasedSchedule(
            count($segments) === 2 ? (int) $segments[1] : 0,
            (int) $segments[0]
        );
    }

    /** Запускать дважды в день.
     * @param int $first  Первый час.
     * @param int $second Второй час.
     */
    public function twiceDaily(int $first = 1, int $second = 13): static
    {
        return $this->twiceDailyAt($first, $second, 0);
    }

    /** Запускать дважды в день в указанную минуту.
     * @param int $first  Первый час.
     * @param int $second Второй час.
     * @param int $offset Минута.
     */
    public function twiceDailyAt(int $first = 1, int $second = 13, int $offset = 0): static
    {
        return $this->hourBasedSchedule($offset, "{$first},{$second}");
    }

    /** Запускать только по рабочим дням (пн–пт). */
    public function weekdays(): static
    {
        return $this->days(DayOfWeek::Monday, DayOfWeek::Friday);
    }

    /** Запускать только по выходным (сб–вс). */
    public function weekends(): static
    {
        return $this->days(DayOfWeek::Saturday, DayOfWeek::Sunday);
    }

    /** Запускать только по понедельникам. */
    public function mondays(): static
    {
        return $this->days(DayOfWeek::Monday);
    }

    /** Запускать только по вторникам. */
    public function tuesdays(): static
    {
        return $this->days(DayOfWeek::Tuesday);
    }

    /** Запускать только по средам. */
    public function wednesdays(): static
    {
        return $this->days(DayOfWeek::Wednesday);
    }

    /** Запускать только по четвергам. */
    public function thursdays(): static
    {
        return $this->days(DayOfWeek::Thursday);
    }

    /** Запускать только по пятницам. */
    public function fridays(): static
    {
        return $this->days(DayOfWeek::Friday);
    }

    /** Запускать только по субботам. */
    public function saturdays(): static
    {
        return $this->days(DayOfWeek::Saturday);
    }

    /** Запускать только по воскресеньям. */
    public function sundays(): static
    {
        return $this->days(DayOfWeek::Sunday);
    }

    /** Задать дни недели.
     * @param DayOfWeek|string|int ...$days День(и) недели.
     */
    public function days(DayOfWeek|string|int ...$days): static
    {
        $values = array_map(
            fn($d) => $d instanceof DayOfWeek ? $d->value : $d,
            $days
        );

        return $this->spliceIntoPosition(5, implode(',', $values));
    }

    // -------------------------------------------------------------------------
    // Недели
    // -------------------------------------------------------------------------

    /** Запускать еженедельно в воскресенье в полночь. */
    public function weekly(): static
    {
        return $this->spliceIntoPosition(1, 0)
                    ->spliceIntoPosition(2, 0)
                    ->days(DayOfWeek::Sunday);
    }

    /** Запускать еженедельно в указанный день и время.
     * @param DayOfWeek|string|int $dayOfWeek День недели.
     * @param string               $time      Время в формате 'HH:MM'.
     */
    public function weeklyOn(DayOfWeek|string|int $dayOfWeek, string $time = '0:0'): static
    {
        $this->dailyAt($time);

        return $this->days($dayOfWeek);
    }

    // -------------------------------------------------------------------------
    // Месяцы
    // -------------------------------------------------------------------------

    /** Запускать ежемесячно 1-го числа в полночь. */
    public function monthly(): static
    {
        return $this->spliceIntoPosition(1, 0)
                    ->spliceIntoPosition(2, 0)
                    ->spliceIntoPosition(3, 1);
    }

    /** Запускать ежемесячно в указанный день и время.
     * @param int    $dayOfMonth День месяца.
     * @param string $time       Время в формате 'HH:MM'.
     */
    public function monthlyOn(int $dayOfMonth = 1, string $time = '0:0'): static
    {
        $this->dailyAt($time);

        return $this->spliceIntoPosition(3, $dayOfMonth);
    }

    /** Запускать дважды в месяц.
     * @param int    $first  Первый день.
     * @param int    $second Второй день.
     * @param string $time   Время в формате 'HH:MM'.
     */
    public function twiceMonthly(int $first = 1, int $second = 16, string $time = '0:0'): static
    {
        $this->dailyAt($time);

        return $this->spliceIntoPosition(3, "{$first},{$second}");
    }

    /** Запускать в последний день текущего месяца.
     * @param string $time Время в формате 'HH:MM'.
     */
    public function lastDayOfMonth(string $time = '0:0'): static
    {
        $this->dailyAt($time);

        return $this->spliceIntoPosition(3, (int) date('t'));
    }

    // -------------------------------------------------------------------------
    // Кварталы / Год
    // -------------------------------------------------------------------------

    /** Запускать ежеквартально (1 января, апреля, июля, октября) в полночь. */
    public function quarterly(): static
    {
        return $this->spliceIntoPosition(1, 0)
                    ->spliceIntoPosition(2, 0)
                    ->spliceIntoPosition(3, 1)
                    ->spliceIntoPosition(4, '1-12/3');
    }

    /** Запускать ежеквартально в указанный день и время.
     * @param int    $dayOfQuarter День квартала.
     * @param string $time         Время в формате 'HH:MM'.
     */
    public function quarterlyOn(int $dayOfQuarter = 1, string $time = '0:0'): static
    {
        $this->dailyAt($time);

        return $this->spliceIntoPosition(3, $dayOfQuarter)
                    ->spliceIntoPosition(4, '1-12/3');
    }

    /** Запускать ежегодно 1 января в полночь. */
    public function yearly(): static
    {
        return $this->spliceIntoPosition(1, 0)
                    ->spliceIntoPosition(2, 0)
                    ->spliceIntoPosition(3, 1)
                    ->spliceIntoPosition(4, 1);
    }

    /** Запускать ежегодно в указанный месяц, день и время.
     * @param int    $month      Месяц (1–12).
     * @param int    $dayOfMonth День месяца.
     * @param string $time       Время в формате 'HH:MM'.
     */
    public function yearlyOn(int $month = 1, int $dayOfMonth = 1, string $time = '0:0'): static
    {
        $this->dailyAt($time);

        return $this->spliceIntoPosition(3, $dayOfMonth)
                    ->spliceIntoPosition(4, $month);
    }

    // -------------------------------------------------------------------------
    // Внутренние хелперы
    // -------------------------------------------------------------------------

    /** Построить расписание на основе минут и часов.
     * @param array|string|int $minutes Минуты.
     * @param array|string|int $hours   Часы.
     */
    protected function hourBasedSchedule(array|string|int $minutes, array|string|int $hours): static
    {
        $minutes = is_array($minutes) ? implode(',', $minutes) : $minutes;
        $hours   = is_array($hours)   ? implode(',', $hours)   : $hours;

        return $this->spliceIntoPosition(1, $minutes)
                    ->spliceIntoPosition(2, $hours);
    }

    /** Заменить поле cron-выражения по позиции.
     * @param int              $position Позиция: 1=мин, 2=час, 3=день, 4=месяц, 5=день_недели.
     * @param array|string|int $value    Новое значение поля.
     */
    protected function spliceIntoPosition(int $position, array|string|int $value): static
    {
        $segments = preg_split('/\s+/', $this->getExpression());
        $segments[$position - 1] = $value;

        return $this->cron(implode(' ', $segments));
    }
}
