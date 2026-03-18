<?php

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

        qi("INSERT IGNORE INTO role_permissions (role_id, permission_id) VALUES (1, 1)");
        qi("INSERT IGNORE INTO role_permissions (role_id, permission_id) VALUES (1, 3)");
        qi("INSERT IGNORE INTO role_permissions (role_id, permission_id) VALUES (2, 5)");
    }
}
