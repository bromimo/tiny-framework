<?php

namespace App\Actions\Auth;

use App\DTOs\LoginDto;
use App\Models\User;
use App\Models\Token;
use App\Facades\Event;
use App\Facades\ApiResponse;
use Random\RandomException;
use App\Events\LoginFailed;
use TinyRouter\Http\Response;
use App\Events\LoginSucceeded;

/** Аутентификация пользователя и выдача токена. */
class LoginAction
{
    /** Выполнить вход: проверить credentials и создать токен.
     * @param LoginDto $dto Данные для входа.
     * @return Response
     * @throws RandomException
     */
    public function run(LoginDto $dto): Response
    {

        $user = User::findByEmail($dto->email);

        // Dummy-хеш для constant-time: password_verify() вызывается всегда,
        // чтобы атакующий не мог определить существование email по времени ответа.
        $hash          = $user?->password ?? '$2y$10$dummyhashtopreventtimingattackspadding000000000000000';
        $passwordValid = password_verify($dto->password, $hash) && $user !== null;

        if (!$passwordValid) {
            Event::dispatch(new LoginFailed($dto->email, $_SERVER['REMOTE_ADDR'] ?? 'unknown'));
            return ApiResponse::error('Invalid credentials.', 401);
        }

        $token = Token::create($user->id);
        Event::dispatch(new LoginSucceeded($user->id, $_SERVER['REMOTE_ADDR'] ?? 'unknown'));

        return ApiResponse::ok([
            'token'      => $token->token,
            'expires_at' => $token->expires_at,
        ]);
    }
}
