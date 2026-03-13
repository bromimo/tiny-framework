<?php

namespace App\Actions\Auth;

use App\Models\Token;
use App\Abstracts\BaseAction;

/** Завершение сессии пользователя: удаление токена. */
class LogoutAction extends BaseAction
{
    /** Удалить bearer-токен текущего запроса. */
    public function run(mixed ...$args): void
    {
        $token = getBearerToken();

        if ($token !== null) {
            Token::deleteByToken($token);
        }
    }
}
