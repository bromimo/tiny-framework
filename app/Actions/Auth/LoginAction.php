<?php

namespace App\Actions\Auth;

use App\DTOs\LoginDto;
use App\Models\User;
use App\Models\Token;
use App\Abstracts\BaseAction;
use App\Facades\ApiResponse;
use Random\RandomException;
use TinyRouter\Http\Response;

/** Аутентификация пользователя и выдача токена. */
class LoginAction extends BaseAction
{
    /** Выполнить вход: проверить credentials и создать токен.
     * @param LoginDto ...$args
     * @return Response
     * @throws RandomException
     */
    public function run(mixed ...$args): Response
    {
        [$dto] = $args;

        $user = User::findByEmail($dto->email);

        // Dummy-хеш для constant-time: password_verify() вызывается всегда,
        // чтобы атакующий не мог определить существование email по времени ответа.
        $hash          = $user?->password ?? '$2y$10$dummyhashtopreventtimingattackspadding000000000000000';
        $passwordValid = password_verify($dto->password, $hash) && $user !== null;

        if (!$passwordValid) {
            return ApiResponse::error('Invalid credentials.', 401);
        }

        $token = Token::create($user->id);

        return ApiResponse::ok([
            'token'      => $token->token,
            'expires_at' => $token->expires_at,
        ]);
    }
}
