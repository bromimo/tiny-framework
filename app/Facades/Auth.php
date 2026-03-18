<?php

namespace App\Facades;

use App\Models\User;
use App\Core\Auth\AuthManager;
use App\Core\Auth\GuardInterface;

/** Фасад аутентификации.
 * @method static User|null user()
 * @method static bool check()
 * @method static int|null id()
 * @method static GuardInterface guard(?string $name = null)
 */
class Auth
{
    private static ?AuthManager $instance = null;

    /** Установить экземпляр AuthManager.
     * @param AuthManager $manager
     * @return void
     */
    public static function setInstance(AuthManager $manager): void
    {
        self::$instance = $manager;
    }

    /** Сбросить экземпляр (для тестов).
     * @return void
     */
    public static function reset(): void
    {
        self::$instance = null;
    }

    /** Получить экземпляр AuthManager.
     * @return AuthManager
     * @throws \RuntimeException Если AuthManager не инициализирован.
     */
    private static function instance(): AuthManager
    {
        if (self::$instance === null) {
            throw new \RuntimeException('AuthManager is not initialized. Call Auth::setInstance() first.');
        }

        return self::$instance;
    }

    /** Получить текущего аутентифицированного пользователя.
     * @return User|null
     */
    public static function user(): ?User
    {
        return self::instance()->user();
    }

    /** Проверить, аутентифицирован ли пользователь.
     * @return bool
     */
    public static function check(): bool
    {
        return self::instance()->check();
    }

    /** Получить ID текущего пользователя.
     * @return int|null
     */
    public static function id(): ?int
    {
        return self::instance()->id();
    }

    /** Получить guard по имени (или дефолтный).
     * @param string|null $name
     * @return GuardInterface
     */
    public static function guard(?string $name = null): GuardInterface
    {
        return self::instance()->guard($name);
    }
}
