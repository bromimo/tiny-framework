<?php

namespace App\Console\Commands\User;

use App\DTOs\UserDto;
use App\Models\User;
use App\Abstracts\BaseCommand;
use App\Exceptions\QueryException;

/** Создаёт нового пользователя в интерактивном режиме или через аргументы. */
class UserCreateCommand extends BaseCommand
{
    public static string $name = 'user:create';

    /** Вернуть краткое описание команды. */
    public function description(): string
    {
        return 'Создать пользователя: [--first-name=] [--last-name=] [--email=] [--password=]';
    }

    /** Выполнить команду.
     * @param array<int, string> $args Аргументы: [--first-name=] [--last-name=] [--email=] [--password=]
     */
    public function handle(array $args): void
    {
        $params = $this->parseArgs($args);

        $firstName = $params['first-name'] ?? $this->ask('First name');
        $lastName  = $params['last-name']  ?? $this->ask('Last name');
        $email     = $params['email']      ?? $this->ask('Email');
        $password  = $params['password']   ?? $this->askPasswordWithConfirmation();

        try {
            $user = User::create(new UserDto(
                first_name: $firstName,
                last_name:  $lastName,
                email:      $email,
                password:   $password,
            ));
        } catch (QueryException $e) {
            if ($e->getSqlState() === '23000') {
                echo self::RED . 'Error: ' . self::RESET . "Email {$email} is already in use." . PHP_EOL;
                return;
            }
            throw $e;
        }

        echo self::GREEN . 'User created successfully.' . self::RESET . PHP_EOL;
        echo self::GRAY  . "  ID:         " . self::RESET . $user->id         . PHP_EOL;
        echo self::GRAY  . "  First name: " . self::RESET . $user->first_name . PHP_EOL;
        echo self::GRAY  . "  Last name:  " . self::RESET . $user->last_name  . PHP_EOL;
        echo self::GRAY  . "  Email:      " . self::RESET . $user->email      . PHP_EOL;
    }

    /** Запросить значение у пользователя.
     * @param string $label Название поля.
     * @return string Введённое значение.
     */
    private function ask(string $label): string
    {
        echo self::GREEN . "  {$label}: " . self::RESET;
        return trim((string) fgets(STDIN));
    }

    /** Запросить пароль с подтверждением. Повторяет запрос если пароли не совпадают.
     * @return string Подтверждённый пароль.
     */
    private function askPasswordWithConfirmation(): string
    {
        while (true) {
            $password = $this->askSecret('Password');
            $confirm  = $this->askSecret('Confirm password');

            if ($password === $confirm) {
                return $password;
            }

            echo self::RED . '  Passwords do not match. Try again.' . self::RESET . PHP_EOL;
        }
    }

    /** Запросить скрытый ввод (пароль).
     * На Unix скрывает символы через stty. На Windows вводится открыто.
     * @param string $label Название поля.
     * @return string Введённое значение.
     */
    private function askSecret(string $label): string
    {
        echo self::GREEN . "  {$label}: " . self::RESET;

        if (PHP_OS_FAMILY !== 'Windows') {
            system('stty -echo');
            $value = trim((string) fgets(STDIN));
            system('stty echo');
            echo PHP_EOL;
        } else {
            $value = trim((string) fgets(STDIN));
        }

        return $value;
    }

    /** Разобрать аргументы вида --key=value в ассоциативный массив.
     * @param array<int, string> $args
     * @return array<string, string>
     */
    private function parseArgs(array $args): array
    {
        $result = [];

        foreach ($args as $arg) {
            if (preg_match('/^--([^=]+)=(.*)$/', $arg, $m)) {
                $result[$m[1]] = $m[2];
            }
        }

        return $result;
    }
}
