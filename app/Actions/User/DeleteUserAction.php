<?php

namespace App\Actions\User;

use App\Models\User;
use App\Abstracts\BaseAction;

/** Удаление пользователя. */
class DeleteUserAction extends BaseAction
{
    /** Удалить пользователя.
     * @param User ...$args
     * @return void
     */
    public function run(mixed ...$args): void
    {
        [$user] = $args;
        User::deleteById($user->id);
    }
}
