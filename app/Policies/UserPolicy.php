<?php

namespace App\Policies;

use App\Models\User;
use App\Core\Auth\PolicyInterface;

/** Политика авторизации для модели User. */
class UserPolicy implements PolicyInterface
{
    /** Может ли пользователь просматривать список пользователей.
     * @param User $authUser
     * @return bool
     */
    public function viewAny(User $authUser): bool
    {
        return $authUser->hasPermission('users.view');
    }

    /** Может ли пользователь просматривать конкретного пользователя.
     * @param User $authUser
     * @param User $targetUser
     * @return bool
     */
    public function view(User $authUser, User $targetUser): bool
    {
        return $authUser->hasPermission('users.view');
    }

    /** Может ли пользователь создавать пользователей.
     * @param User $authUser
     * @return bool
     */
    public function create(User $authUser): bool
    {
        return $authUser->hasPermission('users.create');
    }

    /** Может ли пользователь обновлять указанного пользователя.
     * Admin может любого, обычный пользователь — только себя.
     * @param User $authUser
     * @param User $targetUser
     * @return bool
     */
    public function update(User $authUser, User $targetUser): bool
    {
        if (!$authUser->hasPermission('users.update')) {
            return false;
        }

        if ($authUser->isAdmin()) {
            return true;
        }

        return $authUser->id === $targetUser->id;
    }

    /** Может ли пользователь удалить указанного пользователя.
     * Только admin, и не может удалить себя.
     * @param User $authUser
     * @param User $targetUser
     * @return bool
     */
    public function delete(User $authUser, User $targetUser): bool
    {
        if (!$authUser->hasPermission('users.delete')) {
            return false;
        }

        if (!$authUser->isAdmin()) {
            return false;
        }

        return $authUser->id !== $targetUser->id;
    }
}
