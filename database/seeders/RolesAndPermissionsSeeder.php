<?php

namespace Database\Seeders;

/** Сидер ролей и пермишенов из конфигурации auth.
 * Идемпотентный — безопасно запускать повторно.
 */
class RolesAndPermissionsSeeder extends BaseSeeder
{
    /** Синхронизировать роли и пермишены из config/auth.php. */
    public function run(): void
    {
        $this->seedPermissions();
        $this->seedRoles();

        echo "Roles and permissions seeded." . PHP_EOL;
    }

    /** Создать пермишены из конфига. */
    private function seedPermissions(): void
    {
        $permissions = config('auth.permissions', []);

        foreach ($permissions as $name) {
            qi(
                "INSERT INTO permissions (name) VALUES (?) ON DUPLICATE KEY UPDATE name = name",
                [$name]
            );
        }

        qi("INSERT INTO permissions (name) VALUES ('*') ON DUPLICATE KEY UPDATE name = name");
    }

    /** Создать роли и привязать пермишены. */
    private function seedRoles(): void
    {
        $roles = config('auth.roles', []);
        $roleIndex = 1;

        foreach ($roles as $name => $config) {
            $description = $config['description'] ?? null;

            qi(
                "INSERT INTO roles (id, name, description) VALUES (?, ?, ?)
                 ON DUPLICATE KEY UPDATE description = VALUES(description), name = VALUES(name)",
                [$roleIndex, $name, $description]
            );

            $roleId = q1("SELECT id FROM roles WHERE name = ?", [$name])['id'];

            qi("DELETE FROM role_permissions WHERE role_id = ?", [$roleId]);

            foreach ($config['permissions'] ?? [] as $permName) {
                $perm = q1("SELECT id FROM permissions WHERE name = ?", [$permName]);
                if ($perm) {
                    qi(
                        "INSERT INTO role_permissions (role_id, permission_id) VALUES (?, ?)",
                        [$roleId, $perm['id']]
                    );
                }
            }

            $roleIndex++;
        }
    }
}
