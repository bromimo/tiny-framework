<?php

namespace App\Actions\User;

use App\DTOs\UserDto;
use App\Models\User;
use App\Exceptions\QueryException;
use App\Exceptions\ValidationException;

/** Обновление существующего пользователя. */
class UpdateUserAction
{
    /** Обновить пользователя.
     * @param User $user Пользователь для обновления.
     * @param UserDto $dto Данные для обновления.
     * @return User Обновлённый пользователь.
     * @throws ValidationException Если не передано ни одного поля для обновления.
     * @throws QueryException
     */
    public function run(User $user, UserDto $dto): User
    {

        if ($dto->first_name === '' && $dto->last_name === '' && $dto->email === '' && $dto->password === null) {
            throw new ValidationException(['body' => 'No fields provided for update.']);
        }

        return User::update($user->id, $dto);
    }
}
