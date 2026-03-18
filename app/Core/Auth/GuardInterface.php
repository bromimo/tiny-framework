<?php

namespace App\Core\Auth;

use App\Models\User;
use TinyRouter\Http\Request;

/** Контракт guard-а аутентификации. */
interface GuardInterface
{
    /** Получить текущего аутентифицированного пользователя.
     * @return User|null
     */
    public function user(): ?User;

    /** Проверить, аутентифицирован ли пользователь.
     * @return bool
     */
    public function check(): bool;

    /** Получить ID текущего пользователя.
     * @return int|null
     */
    public function id(): ?int;

    /** Провалидировать запрос и установить пользователя.
     * @param Request $request
     * @return User|null
     */
    public function validate(Request $request): ?User;
}
