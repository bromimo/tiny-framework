<?php

namespace App\Actions\User;

use App\DTOs\UserDto;
use App\Models\User;
use App\Abstracts\BaseAction;
use App\Exceptions\QueryException;

/** Создание нового пользователя. */
class CreateUserAction extends BaseAction
{
    /** Создать пользователя из DTO.
     * @param UserDto ...$args
     * @return User
     * @throws QueryException
     */
    public function run(mixed ...$args): User
    {
        [$dto] = $args;
        return User::create($dto);
    }
}
