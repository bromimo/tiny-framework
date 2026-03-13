<?php

namespace App\Console\Commands\Auth;

use App\Models\Token;
use App\Abstracts\BaseCommand;

/** Удаляет все истёкшие токены авторизации из таблицы tokens. */
class ClearExpiredTokensCommand extends BaseCommand
{
    public static string $name = 'auth:clear-tokens';

    /** Вернуть краткое описание команды. */
    public function description(): string
    {
        return 'Удалить истёкшие токены авторизации.';
    }

    /** Выполнить команду.
     * @param array<int, string> $args Аргументы командной строки.
     */
    public function handle(array $args): void
    {
        $deleted = Token::deleteExpired();

        echo self::GREEN . 'Done: ' . self::RESET . "deleted {$deleted} expired token(s)." . PHP_EOL;
    }
}
