<?php

namespace Database\Factories;

use Faker\Factory;
use App\DTOs\UserDto;
use App\Models\User;

/** Фабрика для генерации тестовых пользователей. */
class UserFactory
{
    /** Сгенерировать массив данных для одного пользователя.
     * @return array{first_name: string, last_name: string, email: string, password: string}
     */
    public static function definition(): array
    {
        $faker = Factory::create();

        return [
            'first_name' => $faker->firstName(),
            'last_name'  => $faker->lastName(),
            'email'      => $faker->unique()->safeEmail(),
            'password'   => 'password',
        ];
    }

    /** Создать и сохранить в БД одного или нескольких пользователей.
     * @param int $count Количество пользователей.
     * @param array<string, mixed> $overrides Поля для переопределения дефолтных значений.
     * @return list<User>
     */
    public static function create(int $count = 1, array $overrides = []): array
    {
        $users = [];

        for ($i = 0; $i < $count; $i++) {
            $users[] = User::create(UserDto::from(array_merge(self::definition(), $overrides)));
        }

        return $users;
    }
}
