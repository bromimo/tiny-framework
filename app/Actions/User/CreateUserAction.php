<?php

namespace App\Actions\User;

use App\DTOs\UserDto;
use App\Models\User;
use App\Exceptions\QueryException;

/** Создание нового пользователя. */
class CreateUserAction
{
    /** Создать пользователя из DTO.
     * @param UserDto $dto Данные нового пользователя.
     * @return User
     * @throws QueryException
     */
    public function run(UserDto $dto): User
    {
        return User::create($dto);
    }
}
