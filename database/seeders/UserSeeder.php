<?php

namespace Database\Seeders;

use Database\Factories\UserFactory;

/** Сидер пользователей. Создаёт набор тестовых записей через UserFactory. */
class UserSeeder extends BaseSeeder
{
    /** Выполнить сидер.
     * @param int $count Количество создаваемых пользователей.
     */
    public function run(int $count = 10): void
    {
        UserFactory::create($count);
        echo "Seeded {$count} users." . PHP_EOL;
    }
}
