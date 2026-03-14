<?php

namespace App\Actions\Auth;

use App\Models\Token;

/** Завершение сессии пользователя: удаление токена. */
class LogoutAction
{
    /** Удалить bearer-токен текущего запроса. */
    public function run(): void
    {
        $token = getBearerToken();

        if ($token !== null) {
            Token::deleteByToken($token);
        }
    }
}
