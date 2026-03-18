<?php

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

    public function test_unauthenticated_request_returns_401(): void
    {
        $this->client->get('/api/v1/users')->assertStatus(401);
    }

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

    public function test_user_cannot_delete_anyone(): void
    {
        $this->client->withToken($this->userToken)
            ->delete("/api/v1/users/{$this->otherUser->id}")
            ->assertStatus(403);
    }

    public function test_admin_can_update_any_user(): void
    {
        $this->client->withToken($this->adminToken)
            ->put("/api/v1/users/{$this->user->id}", ['first_name' => 'AdminUpdated'])
            ->assertStatus(200);
    }

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
        qi("INSERT IGNORE INTO role_permissions (role_id, permission_id) VALUES (1, 1)");
        qi("INSERT IGNORE INTO role_permissions (role_id, permission_id) VALUES (1, 3)");
        qi("INSERT IGNORE INTO role_permissions (role_id, permission_id) VALUES (2, 5)");
    }
}
