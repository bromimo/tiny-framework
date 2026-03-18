<?php

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

    public function test_admin_can_view_any(): void
    {
        $this->assertTrue($this->policy->viewAny($this->admin));
    }

    public function test_user_can_view_any(): void
    {
        $this->assertTrue($this->policy->viewAny($this->user));
    }

    public function test_admin_can_view_any_user(): void
    {
        $this->assertTrue($this->policy->view($this->admin, $this->user));
    }

    public function test_user_can_view_any_user(): void
    {
        $this->assertTrue($this->policy->view($this->user, $this->otherUser));
    }

    public function test_admin_can_create_user(): void
    {
        $this->assertTrue($this->policy->create($this->admin));
    }

    public function test_user_cannot_create_user(): void
    {
        $this->assertFalse($this->policy->create($this->user));
    }

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
        qi("INSERT IGNORE INTO role_permissions (role_id, permission_id) VALUES (1, 1)");
        qi("INSERT IGNORE INTO role_permissions (role_id, permission_id) VALUES (1, 3)");
        qi("INSERT IGNORE INTO role_permissions (role_id, permission_id) VALUES (2, 5)");
    }
}
