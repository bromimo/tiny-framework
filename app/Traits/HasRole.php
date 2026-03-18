<?php

namespace App\Traits;

use App\Models\Role;

/** Трейт для модели с ролью.
 * Подключается к модели User — предоставляет role(), hasRole(), hasPermission(), isAdmin().
 * Ожидает наличие свойства role_id у модели.
 */
trait HasRole
{
    private ?Role $cachedRole = null;

    /** Получить роль пользователя.
     * @return Role|null
     */
    public function role(): ?Role
    {
        if ($this->cachedRole !== null) {
            return $this->cachedRole;
        }

        $this->cachedRole = Role::findById($this->role_id);

        return $this->cachedRole;
    }

    /** Проверить, имеет ли пользователь указанную роль.
     * @param string $roleName
     * @return bool
     */
    public function hasRole(string $roleName): bool
    {
        return $this->role()?->name === $roleName;
    }

    /** Проверить наличие пермишена у пользователя (через роль).
     * @param string $permission
     * @return bool
     */
    public function hasPermission(string $permission): bool
    {
        return $this->role()?->hasPermission($permission) ?? false;
    }

    /** Проверить, является ли пользователь администратором.
     * @return bool
     */
    public function isAdmin(): bool
    {
        return $this->hasRole('admin');
    }
}
