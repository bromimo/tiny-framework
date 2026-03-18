# Authorization (RBAC) Implementation Plan

> **For agentic workers:** REQUIRED: Use superpowers:subagent-driven-development (if subagents available) or superpowers:executing-plans to implement this plan. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add a full RBAC authorization system with Guard abstraction, Policy classes, role/permission models, and middleware integration.

**Architecture:** Layered approach — Guard (authentication abstraction) → Auth facade (current user context) → Gate + Policy (authorization logic). Roles and permissions stored in DB, defaults in config. Two-level authorization: coarse-grained middleware `can:` checks permission strings, fine-grained `authorize()` in controllers calls Policy with ownership logic.

**Tech Stack:** PHP 8.x, custom framework (no Laravel/Symfony), bromimo/tiny-router, PDO/MySQL, PHPUnit

**Spec:** `docs/superpowers/specs/2026-03-18-authorization-rbac-design.md`

---

## File Structure

### New files

```
config/auth.php                                    — expand with guards, defaults, roles, permissions
database/migrations/2026_03_18_000001_create_roles_table.php
database/migrations/2026_03_18_000002_create_permissions_table.php
database/migrations/2026_03_18_000003_create_role_permissions_table.php
database/migrations/2026_03_18_000004_add_role_id_to_users_table.php
database/seeders/RolesAndPermissionsSeeder.php
app/Models/Role.php
app/Models/Permission.php
app/Traits/HasRole.php
app/Core/Auth/GuardInterface.php
app/Core/Auth/TokenGuard.php
app/Core/Auth/AuthManager.php
app/Core/Auth/Gate.php
app/Core/Auth/PolicyInterface.php
app/Facades/Auth.php
app/Exceptions/AuthenticationException.php
app/Exceptions/AuthorizationException.php
app/Policies/UserPolicy.php
app/Http/Middleware/AuthorizationMiddleware.php
tests/Unit/Models/RoleTest.php
tests/Unit/Models/PermissionTest.php
tests/Unit/Traits/HasRoleTest.php
tests/Unit/Core/Auth/TokenGuardTest.php
tests/Unit/Core/Auth/AuthManagerTest.php
tests/Unit/Core/Auth/GateTest.php
tests/Unit/Policies/UserPolicyTest.php
tests/Feature/AuthorizationTest.php
```

### Modified files

```
config/auth.php                                    — add defaults, guards, roles, permissions sections
app/Models/User.php                                — add use HasRole, role_id to $fillable
app/Http/Middleware/AuthMiddleware.php              — delegate to TokenGuard via Auth facade
app/Helpers/helpers.php                            — add authorize() helper
bootstrap/app.php                                  — Auth/Gate init, error handling, can: middleware
routes/api_v1.php                                  — add can: middleware to routes
tests/Support/TestApplication.php                  — add Auth/Gate init, can: factory, exception catch blocks
```

---

### Task 1: Database Migrations

**Files:**
- Create: `database/migrations/2026_03_18_000001_create_roles_table.php`
- Create: `database/migrations/2026_03_18_000002_create_permissions_table.php`
- Create: `database/migrations/2026_03_18_000003_create_role_permissions_table.php`
- Create: `database/migrations/2026_03_18_000004_add_role_id_to_users_table.php`

- [ ] **Step 1: Create roles table migration**

```php
<?php
// database/migrations/2026_03_18_000001_create_roles_table.php

return new class {
    /** Создать таблицу ролей. */
    public function up(): void
    {
        qi("CREATE TABLE IF NOT EXISTS roles (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(50) NOT NULL UNIQUE,
            description VARCHAR(255) NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }

    /** Удалить таблицу ролей. */
    public function down(): void
    {
        qi("DROP TABLE IF EXISTS roles");
    }
};
```

- [ ] **Step 2: Create permissions table migration**

```php
<?php
// database/migrations/2026_03_18_000002_create_permissions_table.php

return new class {
    /** Создать таблицу пермишенов. */
    public function up(): void
    {
        qi("CREATE TABLE IF NOT EXISTS permissions (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL UNIQUE,
            description VARCHAR(255) NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }

    /** Удалить таблицу пермишенов. */
    public function down(): void
    {
        qi("DROP TABLE IF EXISTS permissions");
    }
};
```

- [ ] **Step 3: Create role_permissions pivot table migration**

```php
<?php
// database/migrations/2026_03_18_000003_create_role_permissions_table.php

return new class {
    /** Создать связующую таблицу ролей и пермишенов. */
    public function up(): void
    {
        qi("CREATE TABLE IF NOT EXISTS role_permissions (
            role_id INT UNSIGNED NOT NULL,
            permission_id INT UNSIGNED NOT NULL,
            PRIMARY KEY (role_id, permission_id),
            CONSTRAINT fk_rp_role FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
            CONSTRAINT fk_rp_permission FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }

    /** Удалить связующую таблицу ролей и пермишенов. */
    public function down(): void
    {
        qi("DROP TABLE IF EXISTS role_permissions");
    }
};
```

- [ ] **Step 4: Add role_id to users table migration**

```php
<?php
// database/migrations/2026_03_18_000004_add_role_id_to_users_table.php

return new class {
    /** Добавить колонку role_id в таблицу users. */
    public function up(): void
    {
        qi("ALTER TABLE users ADD COLUMN role_id INT UNSIGNED NOT NULL DEFAULT 1 AFTER password");
        qi("ALTER TABLE users ADD INDEX idx_users_role_id (role_id)");
        qi("ALTER TABLE users ADD CONSTRAINT fk_users_role FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE RESTRICT");
    }

    /** Удалить колонку role_id из таблицы users. */
    public function down(): void
    {
        qi("ALTER TABLE users DROP FOREIGN KEY fk_users_role");
        qi("ALTER TABLE users DROP INDEX idx_users_role_id");
        qi("ALTER TABLE users DROP COLUMN role_id");
    }
};
```

- [ ] **Step 5: Run migrations**

Run: `php console migrate`
Expected: 4 migrations applied successfully

- [ ] **Step 6: Commit**

```bash
git add database/migrations/2026_03_18_000001_create_roles_table.php \
        database/migrations/2026_03_18_000002_create_permissions_table.php \
        database/migrations/2026_03_18_000003_create_role_permissions_table.php \
        database/migrations/2026_03_18_000004_add_role_id_to_users_table.php
git commit -m "feat(auth): add RBAC database migrations

Create roles, permissions, role_permissions tables.
Add role_id (FK, ON DELETE RESTRICT) to users."
```

---

### Task 2: Config Expansion

**Files:**
- Modify: `config/auth.php`

- [ ] **Step 1: Read current config/auth.php**

- [ ] **Step 2: Expand config with guards, roles, permissions**

Add to `config/auth.php`:

```php
return [
    'defaults' => [
        'guard' => 'api',
    ],

    'guards' => [
        'api' => [
            'driver' => 'token',
        ],
        // 'web' => ['driver' => 'session'],
    ],

    'token' => [
        'lifetime' => env('AUTH_TOKEN_LIFETIME', '+28 days'),
    ],

    'password' => [
        'algo'    => PASSWORD_BCRYPT,
        'options' => [],
    ],

    'trusted_proxies' => array_filter(
        explode(',', env('TRUSTED_PROXIES', '')),
        fn(string $ip) => $ip !== ''
    ),

    'roles' => [
        'user' => [
            'description' => 'Пользователь',
            'permissions' => ['users.view', 'users.update'],
        ],
        'admin' => [
            'description' => 'Администратор',
            'permissions' => ['*'],
        ],
    ],

    'permissions' => [
        'users.view',
        'users.create',
        'users.update',
        'users.delete',
    ],
];
```

- [ ] **Step 3: Commit**

```bash
git add config/auth.php
git commit -m "feat(auth): expand auth config with guards, roles, permissions"
```

---

### Task 3: Role & Permission Models

**Files:**
- Create: `app/Models/Role.php`
- Create: `app/Models/Permission.php`
- Create: `tests/Unit/Models/RoleTest.php`
- Create: `tests/Unit/Models/PermissionTest.php`

- [ ] **Step 1: Write failing tests for Role model**

```php
<?php
// tests/Unit/Models/RoleTest.php

namespace Tests\Unit\Models;

use App\Models\Role;
use Tests\Support\FeatureTestCase;

class RoleTest extends FeatureTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRolesAndPermissions();
    }

    public function test_find_by_name_returns_role(): void
    {
        $role = Role::findByName('admin');

        $this->assertInstanceOf(Role::class, $role);
        $this->assertSame('admin', $role->name);
    }

    public function test_find_by_name_returns_null_for_unknown(): void
    {
        $this->assertNull(Role::findByName('nonexistent'));
    }

    public function test_permissions_returns_array_of_permission_names(): void
    {
        $role = Role::findByName('user');
        $permissions = $role->permissions();

        $this->assertIsArray($permissions);
        $this->assertContains('users.view', $permissions);
    }

    public function test_has_permission_with_direct_match(): void
    {
        $role = Role::findByName('user');

        $this->assertTrue($role->hasPermission('users.view'));
        $this->assertFalse($role->hasPermission('users.delete'));
    }

    public function test_has_permission_with_wildcard(): void
    {
        $role = Role::findByName('admin');

        $this->assertTrue($role->hasPermission('users.view'));
        $this->assertTrue($role->hasPermission('users.delete'));
        $this->assertTrue($role->hasPermission('anything.here'));
    }

    public function test_permissions_are_cached_within_instance(): void
    {
        $role = Role::findByName('user');

        $first = $role->permissions();
        $second = $role->permissions();

        $this->assertSame($first, $second);
    }

    private function seedRolesAndPermissions(): void
    {
        qi("INSERT IGNORE INTO roles (id, name, description) VALUES (1, 'user', 'Пользователь')");
        qi("INSERT IGNORE INTO roles (id, name, description) VALUES (2, 'admin', 'Администратор')");

        qi("INSERT IGNORE INTO permissions (id, name) VALUES (1, 'users.view')");
        qi("INSERT IGNORE INTO permissions (id, name) VALUES (2, 'users.create')");
        qi("INSERT IGNORE INTO permissions (id, name) VALUES (3, 'users.update')");
        qi("INSERT IGNORE INTO permissions (id, name) VALUES (4, 'users.delete')");
        qi("INSERT IGNORE INTO permissions (id, name) VALUES (5, '*')");

        qi("INSERT IGNORE INTO role_permissions (role_id, permission_id) VALUES (1, 1)"); // user → users.view
        qi("INSERT IGNORE INTO role_permissions (role_id, permission_id) VALUES (1, 3)"); // user → users.update
        qi("INSERT IGNORE INTO role_permissions (role_id, permission_id) VALUES (2, 5)"); // admin → *
    }
}
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `./vendor/bin/phpunit tests/Unit/Models/RoleTest.php`
Expected: FAIL — class `App\Models\Role` not found

- [ ] **Step 3: Write Role model**

```php
<?php
// app/Models/Role.php

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
```

- [ ] **Step 4: Run Role tests to verify they pass**

Run: `./vendor/bin/phpunit tests/Unit/Models/RoleTest.php`
Expected: All tests PASS

- [ ] **Step 5: Write failing tests for Permission model**

```php
<?php
// tests/Unit/Models/PermissionTest.php

namespace Tests\Unit\Models;

use App\Models\Permission;
use Tests\Support\FeatureTestCase;

class PermissionTest extends FeatureTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        qi("INSERT IGNORE INTO permissions (id, name, description) VALUES (1, 'users.view', 'Просмотр пользователей')");
    }

    public function test_find_by_name_returns_permission(): void
    {
        $perm = Permission::findByName('users.view');

        $this->assertInstanceOf(Permission::class, $perm);
        $this->assertSame('users.view', $perm->name);
    }

    public function test_find_by_name_returns_null_for_unknown(): void
    {
        $this->assertNull(Permission::findByName('nonexistent'));
    }
}
```

- [ ] **Step 6: Run tests to verify they fail**

Run: `./vendor/bin/phpunit tests/Unit/Models/PermissionTest.php`
Expected: FAIL — class `App\Models\Permission` not found

- [ ] **Step 7: Write Permission model**

```php
<?php
// app/Models/Permission.php

namespace App\Models;

use App\Abstracts\BaseModel;

/** Модель пермишена.
 * @property int $id
 * @property string $name
 * @property string|null $description
 * @property string $created_at
 * @property string $updated_at
 */
class Permission extends BaseModel
{
    protected static string $table = 'permissions';

    protected static array $fillable = ['name', 'description'];

    /** Найти пермишен по имени.
     * @param string $name
     * @return static|null
     */
    public static function findByName(string $name): ?static
    {
        return static::findByField('name', $name);
    }
}
```

- [ ] **Step 8: Run Permission tests to verify they pass**

Run: `./vendor/bin/phpunit tests/Unit/Models/PermissionTest.php`
Expected: All tests PASS

- [ ] **Step 9: Commit**

```bash
git add app/Models/Role.php app/Models/Permission.php \
        tests/Unit/Models/RoleTest.php tests/Unit/Models/PermissionTest.php
git commit -m "feat(auth): add Role and Permission models with tests"
```

---

### Task 4: HasRole Trait & User Integration

**Files:**
- Create: `app/Traits/HasRole.php`
- Modify: `app/Models/User.php`
- Create: `tests/Unit/Traits/HasRoleTest.php`

- [ ] **Step 1: Write failing tests for HasRole trait**

```php
<?php
// tests/Unit/Traits/HasRoleTest.php

namespace Tests\Unit\Traits;

use App\DTOs\UserDto;
use App\Models\User;
use Tests\Support\FeatureTestCase;

class HasRoleTest extends FeatureTestCase
{
    private User $adminUser;
    private User $regularUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRolesAndPermissions();

        $this->regularUser = User::create(new UserDto(
            first_name: 'Regular',
            last_name: 'User',
            email: 'user@example.com',
            password: 'password123',
        ));

        $this->adminUser = User::create(new UserDto(
            first_name: 'Admin',
            last_name: 'User',
            email: 'admin@example.com',
            password: 'password123',
        ));
        qi("UPDATE users SET role_id = 2 WHERE id = ?", [$this->adminUser->id]);
        $this->adminUser = User::findById($this->adminUser->id);
    }

    public function test_role_returns_role_model(): void
    {
        $role = $this->regularUser->role();

        $this->assertSame('user', $role->name);
    }

    public function test_has_role_returns_true_for_matching_role(): void
    {
        $this->assertTrue($this->adminUser->hasRole('admin'));
        $this->assertFalse($this->adminUser->hasRole('user'));
    }

    public function test_has_permission_delegates_to_role(): void
    {
        $this->assertTrue($this->regularUser->hasPermission('users.view'));
        $this->assertFalse($this->regularUser->hasPermission('users.delete'));
    }

    public function test_admin_has_all_permissions_via_wildcard(): void
    {
        $this->assertTrue($this->adminUser->hasPermission('users.view'));
        $this->assertTrue($this->adminUser->hasPermission('users.delete'));
        $this->assertTrue($this->adminUser->hasPermission('anything'));
    }

    public function test_is_admin_shortcut(): void
    {
        $this->assertTrue($this->adminUser->isAdmin());
        $this->assertFalse($this->regularUser->isAdmin());
    }

    public function test_role_is_cached_within_instance(): void
    {
        $first = $this->regularUser->role();
        $second = $this->regularUser->role();

        $this->assertSame($first->id, $second->id);
    }

    private function seedRolesAndPermissions(): void
    {
        qi("INSERT IGNORE INTO roles (id, name, description) VALUES (1, 'user', 'Пользователь')");
        qi("INSERT IGNORE INTO roles (id, name, description) VALUES (2, 'admin', 'Администратор')");
        qi("INSERT IGNORE INTO permissions (id, name) VALUES (1, 'users.view')");
        qi("INSERT IGNORE INTO permissions (id, name) VALUES (5, '*')");
        qi("INSERT IGNORE INTO role_permissions (role_id, permission_id) VALUES (1, 1)"); // user → users.view
        qi("INSERT IGNORE INTO role_permissions (role_id, permission_id) VALUES (1, 3)"); // user → users.update
        qi("INSERT IGNORE INTO role_permissions (role_id, permission_id) VALUES (2, 5)"); // admin → *
    }
}
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `./vendor/bin/phpunit tests/Unit/Traits/HasRoleTest.php`
Expected: FAIL — trait `App\Traits\HasRole` not found or `role()` method not found

- [ ] **Step 3: Write HasRole trait**

```php
<?php
// app/Traits/HasRole.php

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
```

- [ ] **Step 4: Modify User model — add HasRole and role_id**

In `app/Models/User.php`:
- Add `use App\Traits\HasRole;` import
- Add `use HasRole;` trait inside class
- Add `'role_id'` to `$fillable` array

```php
// Before:
protected static array $fillable = ['first_name', 'last_name', 'email', 'password'];

// After:
protected static array $fillable = ['first_name', 'last_name', 'email', 'password', 'role_id'];
```

Ensure `use`-imports are sorted by string length (per project conventions).

- [ ] **Step 5: Run HasRole tests to verify they pass**

Run: `./vendor/bin/phpunit tests/Unit/Traits/HasRoleTest.php`
Expected: All tests PASS

- [ ] **Step 6: Run all existing tests to verify no regressions**

Run: `composer test`
Expected: All tests PASS

- [ ] **Step 7: Commit**

```bash
git add app/Traits/HasRole.php app/Models/User.php tests/Unit/Traits/HasRoleTest.php
git commit -m "feat(auth): add HasRole trait and integrate with User model"
```

---

### Task 5: Roles & Permissions Seeder

**Files:**
- Create: `database/seeders/RolesAndPermissionsSeeder.php`
- Modify: `database/seeders/DatabaseSeeder.php`

- [ ] **Step 1: Write RolesAndPermissionsSeeder**

```php
<?php
// database/seeders/RolesAndPermissionsSeeder.php

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

        // Wildcard пермишен для admin
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
                 ON DUPLICATE KEY UPDATE description = VALUES(description)",
                [$roleIndex, $name, $description]
            );

            $roleId = q1("SELECT id FROM roles WHERE name = ?", [$name])['id'];

            // Очистить текущие связи
            qi("DELETE FROM role_permissions WHERE role_id = ?", [$roleId]);

            // Привязать пермишены
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
```

- [ ] **Step 2: Add to DatabaseSeeder**

In `database/seeders/DatabaseSeeder.php`, add `RolesAndPermissionsSeeder::class` to the `$this->call()` array **before** `UserSeeder`:

```php
$this->call([
    RolesAndPermissionsSeeder::class,
    UserSeeder::class,
]);
```

- [ ] **Step 3: Run seeder and verify**

Run: `php console db:seed --class=RolesAndPermissionsSeeder`
Expected: "Roles and permissions seeded."

Verify: `SELECT * FROM roles;` → 2 rows (user, admin)
Verify: `SELECT * FROM permissions;` → 5 rows (4 + wildcard)
Verify: `SELECT * FROM role_permissions;` → 2 rows (user→users.view, admin→*)

- [ ] **Step 4: Commit**

```bash
git add database/seeders/RolesAndPermissionsSeeder.php database/seeders/DatabaseSeeder.php
git commit -m "feat(auth): add RolesAndPermissionsSeeder

Idempotent seeder that syncs roles and permissions from config/auth.php.
Role 'user' is inserted with explicit id=1 for DEFAULT constraint."
```

---

### Task 6: Guard System (GuardInterface, TokenGuard, AuthManager)

**Files:**
- Create: `app/Core/Auth/GuardInterface.php`
- Create: `app/Core/Auth/TokenGuard.php`
- Create: `app/Core/Auth/AuthManager.php`
- Create: `tests/Unit/Core/Auth/TokenGuardTest.php`
- Create: `tests/Unit/Core/Auth/AuthManagerTest.php`

- [ ] **Step 1: Write GuardInterface**

```php
<?php
// app/Core/Auth/GuardInterface.php

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
```

- [ ] **Step 2: Write failing tests for TokenGuard**

```php
<?php
// tests/Unit/Core/Auth/TokenGuardTest.php

namespace Tests\Unit\Core\Auth;

use App\Core\Auth\TokenGuard;
use App\DTOs\UserDto;
use App\Models\Token;
use App\Models\User;
use TinyRouter\Http\Request;
use TinyRouter\Http\Method;
use Tests\Support\FeatureTestCase;

class TokenGuardTest extends FeatureTestCase
{
    private TokenGuard $guard;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoles();
        $this->guard = new TokenGuard();
    }

    public function test_validate_with_valid_token_returns_user(): void
    {
        $user = User::create(new UserDto('John', 'Doe', 'john@example.com', 'secret123'));
        $token = Token::create($user->id);

        $request = new Request(
            method: Method::GET,
            path: '/api/v1/users',
            query: [],
            body: [],
            headers: ['authorization' => 'Bearer ' . $token->token],
        );

        $result = $this->guard->validate($request);

        $this->assertInstanceOf(User::class, $result);
        $this->assertSame($user->id, $result->id);
    }

    public function test_validate_without_token_returns_null(): void
    {
        $request = new Request(
            method: Method::GET,
            path: '/api/v1/users',
            query: [],
            body: [],
            headers: [],
        );

        $this->assertNull($this->guard->validate($request));
    }

    public function test_validate_with_invalid_token_returns_null(): void
    {
        $request = new Request(
            method: Method::GET,
            path: '/api/v1/users',
            query: [],
            body: [],
            headers: ['authorization' => 'Bearer invalid-token-here'],
        );

        $this->assertNull($this->guard->validate($request));
    }

    public function test_user_returns_cached_result_after_validate(): void
    {
        $user = User::create(new UserDto('Jane', 'Doe', 'jane@example.com', 'secret123'));
        $token = Token::create($user->id);

        $request = new Request(
            method: Method::GET,
            path: '/api/v1/users',
            query: [],
            body: [],
            headers: ['authorization' => 'Bearer ' . $token->token],
        );

        $this->guard->validate($request);

        $this->assertSame($user->id, $this->guard->user()->id);
        $this->assertTrue($this->guard->check());
        $this->assertSame($user->id, $this->guard->id());
    }

    public function test_check_returns_false_before_validate(): void
    {
        $this->assertFalse($this->guard->check());
        $this->assertNull($this->guard->user());
        $this->assertNull($this->guard->id());
    }

    private function seedRoles(): void
    {
        qi("INSERT IGNORE INTO roles (id, name, description) VALUES (1, 'user', 'Пользователь')");
    }
}
```

- [ ] **Step 3: Run tests to verify they fail**

Run: `./vendor/bin/phpunit tests/Unit/Core/Auth/TokenGuardTest.php`
Expected: FAIL — class `App\Core\Auth\TokenGuard` not found

- [ ] **Step 4: Write TokenGuard**

```php
<?php
// app/Core/Auth/TokenGuard.php

namespace App\Core\Auth;

use App\Models\User;
use App\Models\Token;
use TinyRouter\Http\Request;

/** Guard аутентификации по Bearer-токену. */
class TokenGuard implements GuardInterface
{
    private ?User $user = null;
    private bool $validated = false;

    /** Получить текущего аутентифицированного пользователя.
     * @return User|null
     */
    public function user(): ?User
    {
        return $this->user;
    }

    /** Проверить, аутентифицирован ли пользователь.
     * @return bool
     */
    public function check(): bool
    {
        return $this->user !== null;
    }

    /** Получить ID текущего пользователя.
     * @return int|null
     */
    public function id(): ?int
    {
        return $this->user?->id;
    }

    /** Провалидировать запрос по Bearer-токену и установить пользователя.
     * @param Request $request
     * @return User|null
     */
    public function validate(Request $request): ?User
    {
        if ($this->validated) {
            return $this->user;
        }

        $this->validated = true;

        $token = $this->extractBearerToken($request);
        if ($token === null) {
            return null;
        }

        $record = Token::findValid($token);
        if ($record === null) {
            return null;
        }

        $this->user = User::findById($record->user_id);

        return $this->user;
    }

    /** Извлечь Bearer-токен из заголовка Authorization.
     * @param Request $request
     * @return string|null
     */
    private function extractBearerToken(Request $request): ?string
    {
        $header = $request->headers['authorization'] ?? '';

        if (preg_match('/^Bearer\s+(.+)$/i', $header, $matches)) {
            return trim($matches[1]);
        }

        return null;
    }
}
```

- [ ] **Step 5: Run TokenGuard tests to verify they pass**

Run: `./vendor/bin/phpunit tests/Unit/Core/Auth/TokenGuardTest.php`
Expected: All tests PASS

- [ ] **Step 6: Write failing tests for AuthManager**

```php
<?php
// tests/Unit/Core/Auth/AuthManagerTest.php

namespace Tests\Unit\Core\Auth;

use App\Core\Auth\AuthManager;
use App\Core\Auth\GuardInterface;
use App\Core\Auth\TokenGuard;
use PHPUnit\Framework\TestCase;

class AuthManagerTest extends TestCase
{
    public function test_guard_returns_default_guard(): void
    {
        $manager = new AuthManager('api', ['api' => new TokenGuard()]);

        $guard = $manager->guard();

        $this->assertInstanceOf(TokenGuard::class, $guard);
    }

    public function test_guard_returns_named_guard(): void
    {
        $tokenGuard = new TokenGuard();
        $manager = new AuthManager('api', ['api' => $tokenGuard]);

        $this->assertSame($tokenGuard, $manager->guard('api'));
    }

    public function test_guard_throws_for_unknown_name(): void
    {
        $manager = new AuthManager('api', ['api' => new TokenGuard()]);

        $this->expectException(\InvalidArgumentException::class);
        $manager->guard('web');
    }

    public function test_user_delegates_to_default_guard(): void
    {
        $manager = new AuthManager('api', ['api' => new TokenGuard()]);

        $this->assertNull($manager->user());
        $this->assertFalse($manager->check());
        $this->assertNull($manager->id());
    }

    public function test_set_user_stores_user_on_guard(): void
    {
        $guard = $this->createMock(GuardInterface::class);
        $guard->method('user')->willReturn(null);
        $guard->method('check')->willReturn(false);

        $manager = new AuthManager('api', ['api' => $guard]);

        $this->assertFalse($manager->check());
    }
}
```

- [ ] **Step 7: Run tests to verify they fail**

Run: `./vendor/bin/phpunit tests/Unit/Core/Auth/AuthManagerTest.php`
Expected: FAIL — class `App\Core\Auth\AuthManager` not found

- [ ] **Step 8: Write AuthManager**

```php
<?php
// app/Core/Auth/AuthManager.php

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
```

- [ ] **Step 9: Run AuthManager tests to verify they pass**

Run: `./vendor/bin/phpunit tests/Unit/Core/Auth/AuthManagerTest.php`
Expected: All tests PASS

- [ ] **Step 10: Commit**

```bash
git add app/Core/Auth/GuardInterface.php app/Core/Auth/TokenGuard.php \
        app/Core/Auth/AuthManager.php \
        tests/Unit/Core/Auth/TokenGuardTest.php tests/Unit/Core/Auth/AuthManagerTest.php
git commit -m "feat(auth): add Guard system (GuardInterface, TokenGuard, AuthManager)"
```

---

### Task 7: Auth Facade

**Files:**
- Create: `app/Facades/Auth.php`

- [ ] **Step 1: Write Auth facade**

```php
<?php
// app/Facades/Auth.php

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
```

- [ ] **Step 2: Commit**

```bash
git add app/Facades/Auth.php
git commit -m "feat(auth): add Auth facade with setInstance pattern"
```

---

### Task 8: Exceptions

**Files:**
- Create: `app/Exceptions/AuthenticationException.php`
- Create: `app/Exceptions/AuthorizationException.php`

- [ ] **Step 1: Write AuthenticationException**

```php
<?php
// app/Exceptions/AuthenticationException.php

namespace App\Exceptions;

use RuntimeException;

/** Исключение аутентификации (401). */
class AuthenticationException extends RuntimeException
{
    /** Создать исключение аутентификации.
     * @param string $message
     */
    public function __construct(string $message = 'Unauthorized.')
    {
        parent::__construct($message);
    }
}
```

- [ ] **Step 2: Write AuthorizationException**

```php
<?php
// app/Exceptions/AuthorizationException.php

namespace App\Exceptions;

use RuntimeException;

/** Исключение авторизации (403). */
class AuthorizationException extends RuntimeException
{
    private string $ability;

    /** Создать исключение авторизации.
     * @param string $message
     * @param string $ability Действие, на которое не хватило прав.
     */
    public function __construct(string $message = 'Forbidden.', string $ability = '')
    {
        parent::__construct($message);
        $this->ability = $ability;
    }

    /** Получить имя действия.
     * @return string
     */
    public function getAbility(): string
    {
        return $this->ability;
    }
}
```

- [ ] **Step 3: Commit**

```bash
git add app/Exceptions/AuthenticationException.php app/Exceptions/AuthorizationException.php
git commit -m "feat(auth): add AuthenticationException (401) and AuthorizationException (403)"
```

---

### Task 9: Gate & Policy System

**Files:**
- Create: `app/Core/Auth/PolicyInterface.php`
- Create: `app/Core/Auth/Gate.php`
- Create: `tests/Unit/Core/Auth/GateTest.php`

- [ ] **Step 1: Write PolicyInterface**

```php
<?php
// app/Core/Auth/PolicyInterface.php

namespace App\Core\Auth;

/** Маркерный интерфейс для Policy-классов. */
interface PolicyInterface
{
}
```

- [ ] **Step 2: Write failing tests for Gate**

```php
<?php
// tests/Unit/Core/Auth/GateTest.php

namespace Tests\Unit\Core\Auth;

use App\Core\Auth\Gate;
use App\Core\Auth\PolicyInterface;
use App\Models\User;
use PHPUnit\Framework\TestCase;

class StubModel
{
    public int $id = 1;
}

class StubPolicy implements PolicyInterface
{
    public bool $viewResult = true;
    public bool $createResult = false;

    public function view(User $authUser, StubModel $model): bool
    {
        return $this->viewResult;
    }

    public function create(User $authUser): bool
    {
        return $this->createResult;
    }
}

class GateTest extends TestCase
{
    private Gate $gate;
    private StubPolicy $policy;

    protected function setUp(): void
    {
        $this->policy = new StubPolicy();
        $this->gate = new Gate();
        $this->gate->register(StubModel::class, $this->policy);
    }

    public function test_authorize_calls_policy_method_with_model_instance(): void
    {
        $user = $this->createStub(User::class);
        $model = new StubModel();

        $this->assertTrue($this->gate->authorize($user, 'view', $model));
    }

    public function test_authorize_calls_policy_method_with_class_string(): void
    {
        $user = $this->createStub(User::class);

        $this->assertFalse($this->gate->authorize($user, 'create', StubModel::class));
    }

    public function test_authorize_throws_for_unregistered_model(): void
    {
        $user = $this->createStub(User::class);

        $this->expectException(\InvalidArgumentException::class);
        $this->gate->authorize($user, 'view', new \stdClass());
    }

    public function test_authorize_throws_for_unknown_ability(): void
    {
        $user = $this->createStub(User::class);
        $model = new StubModel();

        $this->expectException(\BadMethodCallException::class);
        $this->gate->authorize($user, 'nonexistent', $model);
    }

    public function test_denies_is_inverse_of_authorize(): void
    {
        $user = $this->createStub(User::class);
        $model = new StubModel();

        $this->assertFalse($this->gate->denies($user, 'view', $model));
        $this->assertTrue($this->gate->denies($user, 'create', StubModel::class));
    }

    public function test_policy_returns_registered_policy(): void
    {
        $policy = $this->gate->policy(StubModel::class);

        $this->assertSame($this->policy, $policy);
    }
}
```

- [ ] **Step 3: Run tests to verify they fail**

Run: `./vendor/bin/phpunit tests/Unit/Core/Auth/GateTest.php`
Expected: FAIL — class `App\Core\Auth\Gate` not found

- [ ] **Step 4: Write Gate**

```php
<?php
// app/Core/Auth/Gate.php

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
```

- [ ] **Step 5: Run Gate tests to verify they pass**

Run: `./vendor/bin/phpunit tests/Unit/Core/Auth/GateTest.php`
Expected: All tests PASS

- [ ] **Step 6: Commit**

```bash
git add app/Core/Auth/PolicyInterface.php app/Core/Auth/Gate.php \
        tests/Unit/Core/Auth/GateTest.php
git commit -m "feat(auth): add Gate and PolicyInterface for authorization"
```

---

### Task 10: UserPolicy

**Files:**
- Create: `app/Policies/UserPolicy.php`
- Create: `tests/Unit/Policies/UserPolicyTest.php`

- [ ] **Step 1: Write failing tests for UserPolicy**

```php
<?php
// tests/Unit/Policies/UserPolicyTest.php

namespace Tests\Unit\Policies;

use App\DTOs\UserDto;
use App\Models\User;
use App\Policies\UserPolicy;
use Tests\Support\FeatureTestCase;

class UserPolicyTest extends FeatureTestCase
{
    private UserPolicy $policy;
    private User $admin;
    private User $user;
    private User $otherUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRolesAndPermissions();
        $this->policy = new UserPolicy();

        $this->user = User::create(new UserDto('Regular', 'User', 'user@test.com', 'password123'));
        $this->otherUser = User::create(new UserDto('Other', 'User', 'other@test.com', 'password123'));

        $this->admin = User::create(new UserDto('Admin', 'User', 'admin@test.com', 'password123'));
        qi("UPDATE users SET role_id = 2 WHERE id = ?", [$this->admin->id]);
        $this->admin = User::findById($this->admin->id);
    }

    // --- viewAny ---

    public function test_admin_can_view_any(): void
    {
        $this->assertTrue($this->policy->viewAny($this->admin));
    }

    public function test_user_can_view_any(): void
    {
        $this->assertTrue($this->policy->viewAny($this->user));
    }

    // --- view ---

    public function test_admin_can_view_any_user(): void
    {
        $this->assertTrue($this->policy->view($this->admin, $this->user));
    }

    public function test_user_can_view_any_user(): void
    {
        $this->assertTrue($this->policy->view($this->user, $this->otherUser));
    }

    // --- create ---

    public function test_admin_can_create_user(): void
    {
        $this->assertTrue($this->policy->create($this->admin));
    }

    public function test_user_cannot_create_user(): void
    {
        $this->assertFalse($this->policy->create($this->user));
    }

    // --- update ---

    public function test_admin_can_update_any_user(): void
    {
        $this->assertTrue($this->policy->update($this->admin, $this->user));
        $this->assertTrue($this->policy->update($this->admin, $this->otherUser));
    }

    public function test_user_can_update_self(): void
    {
        $this->assertTrue($this->policy->update($this->user, $this->user));
    }

    public function test_user_cannot_update_other(): void
    {
        $this->assertFalse($this->policy->update($this->user, $this->otherUser));
    }

    // --- delete ---

    public function test_admin_can_delete_other_user(): void
    {
        $this->assertTrue($this->policy->delete($this->admin, $this->user));
    }

    public function test_admin_cannot_delete_self(): void
    {
        $this->assertFalse($this->policy->delete($this->admin, $this->admin));
    }

    public function test_user_cannot_delete_anyone(): void
    {
        $this->assertFalse($this->policy->delete($this->user, $this->otherUser));
        $this->assertFalse($this->policy->delete($this->user, $this->user));
    }

    private function seedRolesAndPermissions(): void
    {
        qi("INSERT IGNORE INTO roles (id, name, description) VALUES (1, 'user', 'Пользователь')");
        qi("INSERT IGNORE INTO roles (id, name, description) VALUES (2, 'admin', 'Администратор')");
        qi("INSERT IGNORE INTO permissions (id, name) VALUES (1, 'users.view')");
        qi("INSERT IGNORE INTO permissions (id, name) VALUES (2, 'users.create')");
        qi("INSERT IGNORE INTO permissions (id, name) VALUES (3, 'users.update')");
        qi("INSERT IGNORE INTO permissions (id, name) VALUES (4, 'users.delete')");
        qi("INSERT IGNORE INTO permissions (id, name) VALUES (5, '*')");
        qi("INSERT IGNORE INTO role_permissions (role_id, permission_id) VALUES (1, 1)"); // user → users.view
        qi("INSERT IGNORE INTO role_permissions (role_id, permission_id) VALUES (1, 3)"); // user → users.update
        qi("INSERT IGNORE INTO role_permissions (role_id, permission_id) VALUES (2, 5)"); // admin → *
    }
}
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `./vendor/bin/phpunit tests/Unit/Policies/UserPolicyTest.php`
Expected: FAIL — class `App\Policies\UserPolicy` not found

- [ ] **Step 3: Write UserPolicy**

```php
<?php
// app/Policies/UserPolicy.php

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
```

- [ ] **Step 4: Run UserPolicy tests to verify they pass**

Run: `./vendor/bin/phpunit tests/Unit/Policies/UserPolicyTest.php`
Expected: All tests PASS

- [ ] **Step 5: Commit**

```bash
git add app/Policies/UserPolicy.php tests/Unit/Policies/UserPolicyTest.php
git commit -m "feat(auth): add UserPolicy with ownership and role-based logic"
```

---

### Task 11: authorize() Helper & AuthorizationMiddleware

**Files:**
- Modify: `app/Helpers/helpers.php`
- Create: `app/Http/Middleware/AuthorizationMiddleware.php`

- [ ] **Step 1: Add authorize() helper to helpers.php**

Add to `app/Helpers/helpers.php`:

```php
if (!function_exists('authorize')) {
    /** Авторизовать действие через Gate.
     * @param string $ability Имя действия.
     * @param object|string $model Экземпляр модели или class-string.
     * @return void
     * @throws \App\Exceptions\AuthenticationException Если пользователь не аутентифицирован.
     * @throws \App\Exceptions\AuthorizationException Если действие запрещено.
     */
    function authorize(string $ability, object|string $model): void
    {
        $user = \App\Facades\Auth::user();

        if ($user === null) {
            throw new \App\Exceptions\AuthenticationException();
        }

        $gate = \App\Facades\App::make(\App\Core\Auth\Gate::class);

        if ($gate->denies($user, $ability, $model)) {
            throw new \App\Exceptions\AuthorizationException('Forbidden.', $ability);
        }
    }
}
```

- [ ] **Step 2: Write AuthorizationMiddleware**

```php
<?php
// app/Http/Middleware/AuthorizationMiddleware.php

namespace App\Http\Middleware;

use App\Facades\Auth;
use TinyRouter\Http\Request;
use TinyRouter\Http\Response;
use App\Exceptions\AuthorizationException;
use TinyRouter\Contract\MiddlewareInterface;

/** Middleware проверки пермишена (грубая проверка на уровне роутов).
 * Проверяет Auth::user()->hasPermission($permission).
 * Не вызывает Policy, не резолвит модель.
 */
class AuthorizationMiddleware implements MiddlewareInterface
{
    /** Создать middleware авторизации.
     * @param string $permission Имя пермишена для проверки.
     */
    public function __construct(
        private readonly string $permission,
    ) {}

    /** Обработать запрос.
     * @param Request $request
     * @param callable $next
     * @return Response
     * @throws AuthorizationException Если пермишен отсутствует.
     */
    public function handle(Request $request, callable $next): Response
    {
        $user = Auth::user();

        if ($user === null || !$user->hasPermission($this->permission)) {
            throw new AuthorizationException('Forbidden.', $this->permission);
        }

        return $next($request);
    }
}
```

- [ ] **Step 3: Commit**

```bash
git add app/Helpers/helpers.php app/Http/Middleware/AuthorizationMiddleware.php
git commit -m "feat(auth): add authorize() helper and AuthorizationMiddleware"
```

---

### Task 12: Bootstrap Integration, AuthMiddleware Refactor & TestApplication

**Files:**
- Modify: `bootstrap/app.php`
- Modify: `app/Http/Middleware/AuthMiddleware.php`
- Modify: `tests/Support/TestApplication.php`

- [ ] **Step 1: Read current bootstrap/app.php and AuthMiddleware.php**

- [ ] **Step 2: Refactor AuthMiddleware to delegate to TokenGuard**

Replace the body of `AuthMiddleware::handle()`:

```php
<?php
// app/Http/Middleware/AuthMiddleware.php

namespace App\Http\Middleware;

use App\Facades\Auth;
use TinyRouter\Http\Request;
use TinyRouter\Http\Response;
use App\Exceptions\AuthenticationException;
use TinyRouter\Contract\MiddlewareInterface;

/** Middleware аутентификации по Bearer-токену.
 * Делегирует валидацию текущему guard-у через фасад Auth.
 */
class AuthMiddleware implements MiddlewareInterface
{
    /** Обработать запрос — проверить аутентификацию.
     * @param Request $request
     * @param callable $next
     * @return Response
     * @throws AuthenticationException Если токен невалидный или отсутствует.
     */
    public function handle(Request $request, callable $next): Response
    {
        $user = Auth::guard('api')->validate($request);

        if ($user === null) {
            throw new AuthenticationException();
        }

        return $next($request);
    }
}
```

- [ ] **Step 3: Update bootstrap/app.php — add Auth, Gate initialization and error handling**

Add after existing facade initialization (after `Queue::setInstance(...)` block):

```php
// --- Auth ---
$tokenGuard = new \App\Core\Auth\TokenGuard();
$authManager = new \App\Core\Auth\AuthManager(
    config('auth.defaults.guard', 'api'),
    ['api' => $tokenGuard],
);
\App\Facades\Auth::setInstance($authManager);

// --- Gate ---
$gate = new \App\Core\Auth\Gate();
$gate->register(\App\Models\User::class, new \App\Policies\UserPolicy());
$container->instance(\App\Core\Auth\Gate::class, $gate);
```

Add middleware factory for `can:` alongside existing `rate_limit` factory:

```php
$router->addMiddlewareFactory('can', function (string $params): \App\Http\Middleware\AuthorizationMiddleware {
    return new \App\Http\Middleware\AuthorizationMiddleware($params);
});
```

Add exception catch blocks in the try/catch — **before** the generic `Throwable` catch:

```php
} catch (\App\Exceptions\AuthenticationException $e) {
    \App\Core\ApiResponse::unauthorized($e->getMessage())->send();
} catch (\App\Exceptions\AuthorizationException $e) {
    \App\Core\ApiResponse::error($e->getMessage(), 403)->send();
}
```

- [ ] **Step 4: Update TestApplication.php to mirror bootstrap changes**

Read `tests/Support/TestApplication.php` first. It mirrors `bootstrap/app.php` and must be kept in sync. Add the following changes:

**In the constructor** (alongside existing facade initialization):

```php
// --- Auth ---
$tokenGuard = new \App\Core\Auth\TokenGuard();
$authManager = new \App\Core\Auth\AuthManager(
    config('auth.defaults.guard', 'api'),
    ['api' => $tokenGuard],
);
\App\Facades\Auth::setInstance($authManager);

// --- Gate ---
$gate = new \App\Core\Auth\Gate();
$gate->register(\App\Models\User::class, new \App\Policies\UserPolicy());
$container->instance(\App\Core\Auth\Gate::class, $gate);
```

**Add `can:` middleware factory** (alongside existing `rate_limit` factory):

```php
$router->addMiddlewareFactory('can', function (string $params): \App\Http\Middleware\AuthorizationMiddleware {
    return new \App\Http\Middleware\AuthorizationMiddleware($params);
});
```

**Add exception catch blocks in dispatch()** — before the generic `Throwable` catch:

```php
} catch (\App\Exceptions\AuthenticationException $e) {
    return \App\Core\ApiResponse::unauthorized($e->getMessage());
} catch (\App\Exceptions\AuthorizationException $e) {
    return \App\Core\ApiResponse::error($e->getMessage(), 403);
}
```

**Add `Auth::reset()` in the tearDown/reset method** (alongside other facade resets):

```php
\App\Facades\Auth::reset();
```

- [ ] **Step 5: Run all existing tests to verify no regressions**

Run: `composer test`
Expected: All tests PASS (AuthMiddleware now throws exception instead of returning response, caught by updated error handling in both bootstrap and TestApplication)

- [ ] **Step 6: Commit**

```bash
git add bootstrap/app.php app/Http/Middleware/AuthMiddleware.php tests/Support/TestApplication.php
git commit -m "feat(auth): integrate Auth/Gate into bootstrap, refactor AuthMiddleware

AuthMiddleware delegates to TokenGuard via Auth facade.
Bootstrap and TestApplication initialize AuthManager, Gate, can: middleware factory.
Error handling catches AuthenticationException (401) and AuthorizationException (403)."
```

---

### Task 13: Controller & Route Changes

**Files:**
- Modify: `app/Http/Controllers/Api/V1/UserController.php`
- Modify: `routes/api_v1.php`

- [ ] **Step 1: Read current UserController.php and routes/api_v1.php**

- [ ] **Step 2: Add authorize() calls to UserController**

```php
// UserController::index()
public function index(Request $request): Response
{
    authorize('viewAny', User::class);
    $result = User::paginate($request);
    return success(UserResource::collection($result['data']), $result['meta']);
}

// UserController::show()
public function show(User $user): Response
{
    authorize('view', $user);
    return success(UserResource::make($user));
}

// UserController::store()
public function store(CreateUserRequest $req, CreateUserAction $action): Response
{
    authorize('create', User::class);
    return created(UserResource::make($action->run($req->toDto())));
}

// UserController::update()
public function update(UpdateUserRequest $req, User $user, UpdateUserAction $action): Response
{
    authorize('update', $user);
    return success(UserResource::make($action->run($user, $req->toDto())));
}

// UserController::destroy()
public function destroy(User $user, DeleteUserAction $action): Response
{
    authorize('delete', $user);
    $action->run($user);
    return success(['message' => 'User deleted successfully.']);
}
```

Update the `use` imports in UserController: add `App\Models\User` if not already present.

- [ ] **Step 3: Add can: middleware to routes**

In `routes/api_v1.php`, add `can:` middleware to the protected routes group:

```php
// Users routes with authorization
Route::get('/api/v1/users', [UserController::class, 'index'])
    ->middleware('auth:api', 'can:users.view', 'rate_limit:60,60');

Route::get('/api/v1/users/{id}', [UserController::class, 'show'])
    ->middleware('auth:api', 'can:users.view', 'rate_limit:60,60');

Route::post('/api/v1/users', [UserController::class, 'store'])
    ->middleware('auth:api', 'can:users.create', 'rate_limit:60,60');

Route::match(['PUT', 'PATCH'], '/api/v1/users/{id}', [UserController::class, 'update'])
    ->middleware('auth:api', 'can:users.update', 'rate_limit:60,60');

Route::delete('/api/v1/users/{id}', [UserController::class, 'destroy'])
    ->middleware('auth:api', 'can:users.delete', 'rate_limit:60,60');
```

Adapt to actual route registration syntax in the file (read it first to match existing patterns).

- [ ] **Step 4: Run all tests to verify**

Run: `composer test`
Expected: All tests PASS

- [ ] **Step 5: Commit**

```bash
git add app/Http/Controllers/Api/V1/UserController.php routes/api_v1.php
git commit -m "feat(auth): add authorization to UserController and routes

Controllers call authorize() for Policy checks.
Routes add can: middleware for permission-level checks."
```

---

### Task 14: Integration Tests

**Files:**
- Create: `tests/Feature/AuthorizationTest.php`

- [ ] **Step 1: Write integration tests**

```php
<?php
// tests/Feature/AuthorizationTest.php

namespace Tests\Feature;

use App\DTOs\UserDto;
use App\Models\Token;
use App\Models\User;
use Tests\Support\FeatureTestCase;

class AuthorizationTest extends FeatureTestCase
{
    private User $admin;
    private User $user;
    private User $otherUser;
    private string $adminToken;
    private string $userToken;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRolesAndPermissions();

        $this->user = User::create(new UserDto('Regular', 'User', 'user@test.com', 'password123'));
        $this->otherUser = User::create(new UserDto('Other', 'User', 'other@test.com', 'password123'));

        $this->admin = User::create(new UserDto('Admin', 'User', 'admin@test.com', 'password123'));
        qi("UPDATE users SET role_id = 2 WHERE id = ?", [$this->admin->id]);
        $this->admin = User::findById($this->admin->id);

        $this->userToken = Token::create($this->user->id)->token;
        $this->adminToken = Token::create($this->admin->id)->token;
    }

    // --- Unauthenticated ---

    public function test_unauthenticated_request_returns_401(): void
    {
        $this->client->get('/api/v1/users')->assertStatus(401);
    }

    // --- User: view ---

    public function test_user_can_list_users(): void
    {
        $this->client->withToken($this->userToken)
            ->get('/api/v1/users')
            ->assertStatus(200);
    }

    public function test_user_can_view_other_user(): void
    {
        $this->client->withToken($this->userToken)
            ->get("/api/v1/users/{$this->otherUser->id}")
            ->assertStatus(200);
    }

    // --- User: create ---

    public function test_user_cannot_create_user(): void
    {
        $this->client->withToken($this->userToken)->post('/api/v1/users', [
            'first_name' => 'New',
            'last_name' => 'User',
            'email' => 'new@test.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertStatus(403);
    }

    // --- User: update ---

    public function test_user_can_update_self(): void
    {
        $this->client->withToken($this->userToken)
            ->put("/api/v1/users/{$this->user->id}", ['first_name' => 'Updated'])
            ->assertStatus(200);
    }

    public function test_user_cannot_update_other(): void
    {
        $this->client->withToken($this->userToken)
            ->put("/api/v1/users/{$this->otherUser->id}", ['first_name' => 'Hacked'])
            ->assertStatus(403);
    }

    // --- User: delete ---

    public function test_user_cannot_delete_anyone(): void
    {
        $this->client->withToken($this->userToken)
            ->delete("/api/v1/users/{$this->otherUser->id}")
            ->assertStatus(403);
    }

    // --- Admin: update ---

    public function test_admin_can_update_any_user(): void
    {
        $this->client->withToken($this->adminToken)
            ->put("/api/v1/users/{$this->user->id}", ['first_name' => 'AdminUpdated'])
            ->assertStatus(200);
    }

    // --- Admin: delete ---

    public function test_admin_can_delete_other_user(): void
    {
        $this->client->withToken($this->adminToken)
            ->delete("/api/v1/users/{$this->otherUser->id}")
            ->assertStatus(200);
    }

    public function test_admin_cannot_delete_self(): void
    {
        $this->client->withToken($this->adminToken)
            ->delete("/api/v1/users/{$this->admin->id}")
            ->assertStatus(403);
    }

    // --- Admin: create ---

    public function test_admin_can_create_user(): void
    {
        $this->client->withToken($this->adminToken)->post('/api/v1/users', [
            'first_name' => 'Created',
            'last_name' => 'ByAdmin',
            'email' => 'created@test.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertStatus(201);
    }

    // --- Permission-based 403 ---

    public function test_user_without_view_permission_gets_403(): void
    {
        qi("INSERT IGNORE INTO roles (id, name, description) VALUES (3, 'restricted', 'Без пермишенов')");
        $restricted = User::create(new UserDto('Restricted', 'User', 'restricted@test.com', 'password123'));
        qi("UPDATE users SET role_id = 3 WHERE id = ?", [$restricted->id]);
        $restrictedToken = Token::create($restricted->id)->token;

        $this->client->withToken($restrictedToken)
            ->get('/api/v1/users')
            ->assertStatus(403);
    }

    private function seedRolesAndPermissions(): void
    {
        qi("INSERT IGNORE INTO roles (id, name, description) VALUES (1, 'user', 'Пользователь')");
        qi("INSERT IGNORE INTO roles (id, name, description) VALUES (2, 'admin', 'Администратор')");
        qi("INSERT IGNORE INTO permissions (id, name) VALUES (1, 'users.view')");
        qi("INSERT IGNORE INTO permissions (id, name) VALUES (2, 'users.create')");
        qi("INSERT IGNORE INTO permissions (id, name) VALUES (3, 'users.update')");
        qi("INSERT IGNORE INTO permissions (id, name) VALUES (4, 'users.delete')");
        qi("INSERT IGNORE INTO permissions (id, name) VALUES (5, '*')");
        qi("INSERT IGNORE INTO role_permissions (role_id, permission_id) VALUES (1, 1)"); // user → users.view
        qi("INSERT IGNORE INTO role_permissions (role_id, permission_id) VALUES (1, 3)"); // user → users.update
        qi("INSERT IGNORE INTO role_permissions (role_id, permission_id) VALUES (2, 5)"); // admin → *
    }
}
```

- [ ] **Step 2: Run integration tests**

Run: `./vendor/bin/phpunit tests/Feature/AuthorizationTest.php`
Expected: All tests PASS

- [ ] **Step 3: Run full test suite**

Run: `composer test`
Expected: All tests PASS, no regressions

- [ ] **Step 4: Commit**

```bash
git add tests/Feature/AuthorizationTest.php
git commit -m "test(auth): add integration tests for authorization

Tests cover: unauthenticated 401, user view/update-self/cannot-delete,
admin update-any/delete-other/cannot-delete-self, permission-based 403."
```

---

### Task 15: Fix Existing Feature Tests

After adding RBAC, existing feature tests will break because:
- `users.role_id` FK requires `roles` table to have rows (any INSERT into users fails without it)
- Routes now have `can:` middleware requiring permissions
- `AuthMiddleware` now throws `AuthenticationException` instead of returning Response

**Files:**
- Modify: `tests/Support/FeatureTestCase.php` — seed minimal roles data in setUp
- Modify: `tests/Feature/Http/UserControllerTest.php` — assign admin role where needed
- Modify: `tests/Feature/Http/AuthControllerTest.php` — verify tests still pass
- Modify: `tests/Feature/Http/AuthMiddlewareTest.php` — verify tests still pass

- [ ] **Step 1: Add minimal RBAC seeding to FeatureTestCase::setUp()**

In `tests/Support/FeatureTestCase.php`, add after `DB::query('START TRANSACTION')`:

```php
// Минимальные данные RBAC для всех feature-тестов
qi("INSERT IGNORE INTO roles (id, name) VALUES (1, 'user')");
qi("INSERT IGNORE INTO roles (id, name) VALUES (2, 'admin')");
qi("INSERT IGNORE INTO permissions (id, name) VALUES (1, 'users.view')");
qi("INSERT IGNORE INTO permissions (id, name) VALUES (2, 'users.create')");
qi("INSERT IGNORE INTO permissions (id, name) VALUES (3, 'users.update')");
qi("INSERT IGNORE INTO permissions (id, name) VALUES (4, 'users.delete')");
qi("INSERT IGNORE INTO permissions (id, name) VALUES (5, '*')");
qi("INSERT IGNORE INTO role_permissions (role_id, permission_id) VALUES (1, 1)");
qi("INSERT IGNORE INTO role_permissions (role_id, permission_id) VALUES (1, 3)");
qi("INSERT IGNORE INTO role_permissions (role_id, permission_id) VALUES (2, 5)");
```

- [ ] **Step 2: Update existing feature tests if needed**

Read each existing feature test file. Check if any test creates users with admin privileges — if so, assign `role_id = 2` via `qi("UPDATE users SET role_id = 2 WHERE id = ?", [$admin->id])`.

Check `UserControllerTest` specifically — it likely creates an admin user for CRUD tests. That user now needs admin role.

- [ ] **Step 3: Add Auth::reset() to FeatureTestCase::tearDown()**

In `tests/Support/FeatureTestCase.php`, add before `parent::tearDown()`:

```php
\App\Facades\Auth::reset();
```

- [ ] **Step 4: Run full test suite**

Run: `composer test`
Expected: All tests PASS

- [ ] **Step 5: Commit**

```bash
git add tests/Support/FeatureTestCase.php tests/Feature/Http/
git commit -m "fix(test): update existing feature tests for RBAC compatibility

Seed roles/permissions in FeatureTestCase::setUp().
Assign admin role where needed. Reset Auth facade in tearDown."
```

---

### Task 16: Cleanup & Final Verification

- [ ] **Step 1: Remove or deprecate getBearerToken() helper**

If `getBearerToken()` is only used by `AuthMiddleware` (which now delegates to `TokenGuard`), remove it from `app/Helpers/helpers.php`. If it's used elsewhere, mark as deprecated.

- [ ] **Step 2: Update TestApplication PHPDoc**

The `populateServerFromRequest()` PHPDoc mentions "чтобы getBearerToken() работал корректно" — update to reflect that `populateServerFromRequest()` is now needed for `$_SERVER` compatibility with other superglobal readers, or remove the reference to `getBearerToken()`.

- [ ] **Step 3: Sort use-imports in all changed files**

Per project convention: sort `use` imports by ascending string length in every new and modified file.

- [ ] **Step 4: Verify PHPDocs on all new/modified classes**

All new classes should have PHPDocs in Russian on the first line after `/**`. All `@param`, `@return`, `@throws` tags present.

- [ ] **Step 5: Run full test suite**

Run: `composer test`
Expected: All tests PASS

- [ ] **Step 6: Final commit**

```bash
git add -A
git commit -m "chore(auth): cleanup imports, PHPDocs, remove unused getBearerToken helper"
```
