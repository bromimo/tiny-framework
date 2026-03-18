<?php

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
        qi("INSERT IGNORE INTO role_permissions (role_id, permission_id) VALUES (1, 1)");
        qi("INSERT IGNORE INTO role_permissions (role_id, permission_id) VALUES (1, 3)");
        qi("INSERT IGNORE INTO role_permissions (role_id, permission_id) VALUES (2, 5)");
    }
}
