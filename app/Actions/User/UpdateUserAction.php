<?php

namespace App\Actions\User;

use App\DTOs\UserDto;
use App\Models\User;
use App\Abstracts\BaseAction;
use App\Exceptions\QueryException;
use App\Exceptions\ValidationException;

/** Обновление существующего пользователя. */
class UpdateUserAction extends BaseAction
{
    /** Обновить пользователя.
     * @param mixed ...$args User $user, UserDto $dto
     * @return User Обновлённый пользователь.
     * @throws ValidationException Если не передано ни одного поля для обновления.
     * @throws QueryException
     */
    public function run(mixed ...$args): User
    {
        [$user, $dto] = $args;

        if ($dto->first_name === '' && $dto->last_name === '' && $dto->email === '' && $dto->password === null) {
            throw new ValidationException(['body' => 'No fields provided for update.']);
        }

        return User::update($user->id, $dto);
    }
}
