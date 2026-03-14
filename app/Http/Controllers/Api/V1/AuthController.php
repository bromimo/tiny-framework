<?php

namespace App\Http\Controllers\Api\V1;

use Random\RandomException;
use TinyRouter\Http\Response;
use App\Actions\Auth\LoginAction;
use App\Actions\Auth\LogoutAction;
use App\Exceptions\ValidationException;
use App\Http\Requests\Api\V1\LoginRequest;

/** Контроллер аутентификации. */
class AuthController
{
    /** Вход пользователя.
     * @param LoginRequest $req
     * @param LoginAction  $action
     * @return Response
     * @throws RandomException
     * @throws ValidationException
     */
    public function login(LoginRequest $req, LoginAction $action): Response
    {
        return $action->run($req->toDto());
    }

    /** Выход пользователя.
     * @param LogoutAction $action
     * @return Response
     */
    public function logout(LogoutAction $action): Response
    {
        $action->run();
        return success(['message' => 'Logged out successfully.']);
    }
}
