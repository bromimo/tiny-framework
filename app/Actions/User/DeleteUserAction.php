<?php

namespace App\Actions\User;

use App\Models\User;

/** Удаление пользователя. */
class DeleteUserAction
{
    /** Удалить пользователя.
     * @param User $user Пользователь для удаления.
     */
    public function run(User $user): void
    {
        User::deleteById($user->id);
    }
}
