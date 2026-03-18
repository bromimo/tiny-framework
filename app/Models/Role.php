<?php

namespace App\Models;

use App\Abstracts\BaseModel;

/** Модель роли пользователя.
 * @property int $id
 * @property string $name
 * @property string|null $description
 * @property string $created_at
 * @property string $updated_at
 */
class Role extends BaseModel
{
    protected static string $table = 'roles';

    protected static array $fillable = ['name', 'description'];

    private ?array $cachedPermissions = null;

    /** Найти роль по имени.
     * @param string $name
     * @return static|null
     */
    public static function findByName(string $name): ?static
    {
        return static::findByField('name', $name);
    }

    /** Получить список имён пермишенов роли.
     * @return array<string>
     */
    public function permissions(): array
    {
        if ($this->cachedPermissions !== null) {
            return $this->cachedPermissions;
        }

        $rows = q(
            "SELECT p.name FROM permissions p
             INNER JOIN role_permissions rp ON rp.permission_id = p.id
             WHERE rp.role_id = ?",
            [$this->id]
        );

        $this->cachedPermissions = array_column($rows, 'name');

        return $this->cachedPermissions;
    }

    /** Проверить наличие пермишена у роли.
     * @param string $permission Имя пермишена в dot-нотации.
     * @return bool
     */
    public function hasPermission(string $permission): bool
    {
        $permissions = $this->permissions();

        if (in_array('*', $permissions, true)) {
            return true;
        }

        return in_array($permission, $permissions, true);
    }
}
