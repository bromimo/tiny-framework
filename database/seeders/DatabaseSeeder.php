<?php

namespace Database\Seeders;

/** Главный сидер. Вызывает остальные сидеры в нужном порядке. */
class DatabaseSeeder extends BaseSeeder
{
    /** Выполнить все сидеры. */
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
        ]);
    }
}
