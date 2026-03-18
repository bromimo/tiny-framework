<?php

namespace App\Core\Auth;

use App\Models\User;
use InvalidArgumentException;

/** Менеджер guard-ов аутентификации.
 * Хранит guard-ы по имени, делегирует вызовы дефолтному guard-у.
 */
class AuthManager
{
    /** @var array<string, GuardInterface> */
    private array $guards;
    private string $defaultGuard;

    /** Создать менеджер guard-ов.
     * @param string $defaultGuard Имя дефолтного guard-а.
     * @param array<string, GuardInterface> $guards Массив guard-ов по имени.
     */
    public function __construct(string $defaultGuard, array $guards)
    {
        $this->defaultGuard = $defaultGuard;
        $this->guards = $guards;
    }

    /** Получить guard по имени (или дефолтный).
     * @param string|null $name
     * @return GuardInterface
     * @throws InvalidArgumentException Если guard не зарегистрирован.
     */
    public function guard(?string $name = null): GuardInterface
    {
        $name ??= $this->defaultGuard;

        if (!isset($this->guards[$name])) {
            throw new InvalidArgumentException("Guard [{$name}] is not registered.");
        }

        return $this->guards[$name];
    }

    /** Получить текущего аутентифицированного пользователя.
     * @return User|null
     */
    public function user(): ?User
    {
        return $this->guard()->user();
    }

    /** Проверить, аутентифицирован ли пользователь.
     * @return bool
     */
    public function check(): bool
    {
        return $this->guard()->check();
    }

    /** Получить ID текущего пользователя.
     * @return int|null
     */
    public function id(): ?int
    {
        return $this->guard()->id();
    }
}
