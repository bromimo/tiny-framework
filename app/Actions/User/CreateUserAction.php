<?php

namespace App\Actions\User;

use App\DTOs\UserDto;
use App\Models\User;
use App\Facades\Event;
use App\Abstracts\BaseAction;
use App\Events\UserCreated;
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
        $user = User::create($dto);
        Event::dispatch(new UserCreated($user->id, $dto->email));
        return $user;
    }
}
