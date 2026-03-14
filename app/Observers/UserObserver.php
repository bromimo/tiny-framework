<?php

namespace App\Observers;

use App\Models\User;
use App\Facades\Event;
use App\Events\UserCreated;
use App\Events\UserDeleted;
use App\Events\UserUpdated;

/** Обсервер модели User. Диспатчит события при CRUD-операциях. */
class UserObserver
{
    /** Обработать создание пользователя.
     * @param User $user Созданный пользователь.
     */
    public function created(User $user): void
    {
        Event::dispatch(new UserCreated($user->id, $user->email));
    }

    /** Обработать обновление пользователя.
     * @param User               $user          Обновлённый пользователь.
     * @param array<int, string> $changedFields Имена изменённых полей.
     */
    public function updated(User $user, array $changedFields): void
    {
        Event::dispatch(new UserUpdated($user->id, $changedFields));
    }

    /** Обработать удаление пользователя.
     * @param int $id ID удалённого пользователя.
     */
    public function deleted(int $id): void
    {
        Event::dispatch(new UserDeleted($id));
    }
}
