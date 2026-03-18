<?php

namespace App\Core\Auth;

use App\Models\User;
use BadMethodCallException;
use InvalidArgumentException;

/** Реестр Policy-классов и точка входа для авторизации.
 * Маппит модели на Policy, вызывает соответствующие методы Policy.
 */
class Gate
{
    /** @var array<string, PolicyInterface> Маппинг class-string модели → Policy */
    private array $policies = [];

    /** Зарегистрировать Policy для модели.
     * @param string $modelClass Class-string модели.
     * @param PolicyInterface $policy Экземпляр Policy.
     * @return void
     */
    public function register(string $modelClass, PolicyInterface $policy): void
    {
        $this->policies[$modelClass] = $policy;
    }

    /** Получить Policy для модели.
     * @param string $modelClass
     * @return PolicyInterface
     * @throws InvalidArgumentException Если Policy не зарегистрирован.
     */
    public function policy(string $modelClass): PolicyInterface
    {
        if (!isset($this->policies[$modelClass])) {
            throw new InvalidArgumentException("No policy registered for [{$modelClass}].");
        }

        return $this->policies[$modelClass];
    }

    /** Проверить авторизацию действия.
     * @param User $authUser Текущий аутентифицированный пользователь.
     * @param string $ability Имя действия (view, create, update, delete).
     * @param object|string $model Экземпляр модели или class-string (для create/viewAny).
     * @return bool
     * @throws InvalidArgumentException Если Policy не зарегистрирован.
     * @throws BadMethodCallException Если метод ability не существует в Policy.
     */
    public function authorize(User $authUser, string $ability, object|string $model): bool
    {
        $modelClass = is_string($model) ? $model : $model::class;
        $policy = $this->policy($modelClass);

        if (!method_exists($policy, $ability)) {
            throw new BadMethodCallException(
                "Method [{$ability}] does not exist on policy [" . $policy::class . "]."
            );
        }

        if (is_string($model)) {
            return $policy->$ability($authUser);
        }

        return $policy->$ability($authUser, $model);
    }

    /** Проверить, запрещено ли действие (инверсия authorize).
     * @param User $authUser
     * @param string $ability
     * @param object|string $model
     * @return bool
     */
    public function denies(User $authUser, string $ability, object|string $model): bool
    {
        return !$this->authorize($authUser, $ability, $model);
    }
}
