<?php

namespace Tests\Feature\Http;

use App\Facades\Cache;
use App\Facades\Config;
use Tests\Support\TestClient;
use App\Core\Cache\CacheContract;
use Tests\Support\FeatureTestCase;
use App\Core\Cache\Drivers\ArrayDriver;

/** Интеграционные тесты контроллера пользователей. */
class UserControllerTest extends FeatureTestCase
{
    private string $token;
    private CacheContract $originalDriver;

    /** Создать администратора и получить токен перед каждым тестом.
     * Подменяет кеш на ArrayDriver, чтобы rate limiter не блокировал setUp().
     * После подмены перезагружает конфиги, т.к. Config хранит значения в Cache.
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->originalDriver = Cache::getDriver();
        Cache::setDriver(new ArrayDriver());
        Config::load(base_path('config'));

        // Создать пользователя-администратора и получить токен для всех тестов
        $hash = password_hash('admin123', PASSWORD_BCRYPT);
        qi("INSERT INTO users (first_name, last_name, email, password, role_id) VALUES ('Admin', 'User', 'admin@test.com', ?, 2)", [$hash]);

        $loginResponse = $this->client->post('/api/v1/auth/login', [
            'email'    => 'admin@test.com',
            'password' => 'admin123',
        ]);
        $this->token = $loginResponse->json()['data']['token'];
    }

    /** Восстановить оригинальный драйвер кеша и конфиги после теста.
     * @return void
     */
    protected function tearDown(): void
    {
        Cache::setDriver($this->originalDriver);
        Config::load(base_path('config'));
        parent::tearDown();
    }

    /** Вернуть клиент с токеном авторизации.
     * @return TestClient
     */
    private function authed(): TestClient
    {
        return $this->client->withToken($this->token);
    }

    // GET /api/v1/users

    public function test_index_returns_paginated_response(): void
    {
        $response = $this->authed()->get('/api/v1/users');

        $response->assertStatus(200)
                 ->assertJsonHasPath('data')
                 ->assertJsonHasPath('meta.total')
                 ->assertJsonHasPath('meta.per_page')
                 ->assertJsonHasPath('meta.current_page')
                 ->assertJsonHasPath('meta.last_page');
    }

    public function test_index_respects_per_page_query_param(): void
    {
        // Вставить дополнительных пользователей
        for ($i = 0; $i < 5; $i++) {
            $hash = password_hash('pw', PASSWORD_BCRYPT);
            qi("INSERT INTO users (first_name, last_name, email, password) VALUES ('F{$i}', 'L{$i}', 'user{$i}@test.com', ?)", [$hash]);
        }

        $response = $this->authed()->get('/api/v1/users', ['per_page' => 2]);

        $response->assertStatus(200)
                 ->assertJsonPath('meta.per_page', 2);
    }

    // GET /api/v1/users/{id}

    public function test_show_returns_user(): void
    {
        $id = (int) q1("SELECT id FROM users WHERE email = 'admin@test.com'")['id'];

        $response = $this->authed()->get("/api/v1/users/{$id}");

        $response->assertStatus(200)
                 ->assertJsonPath('data.email', 'admin@test.com');
    }

    public function test_show_returns_404_for_missing_user(): void
    {
        $response = $this->authed()->get('/api/v1/users/99999');

        $response->assertStatus(404);
    }

    // POST /api/v1/users

    public function test_store_creates_user_and_returns_201(): void
    {
        $response = $this->authed()->post('/api/v1/users', [
            'first_name'            => 'New',
            'last_name'             => 'User',
            'email'                 => 'new@test.com',
            'password'              => 'secret123',
            'password_confirmation' => 'secret123',
        ]);

        $response->assertStatus(201)
                 ->assertJsonPath('data.email', 'new@test.com');
    }

    public function test_store_returns_422_for_invalid_data(): void
    {
        $response = $this->authed()->post('/api/v1/users', [
            'email' => 'not-an-email',
        ]);

        $response->assertStatus(422);
    }

    public function test_store_returns_422_for_duplicate_email(): void
    {
        $this->authed()->post('/api/v1/users', [
            'first_name'            => 'Dup',
            'last_name'             => 'User',
            'email'                 => 'dup@test.com',
            'password'              => 'secret123',
            'password_confirmation' => 'secret123',
        ]);

        $response = $this->authed()->post('/api/v1/users', [
            'first_name'            => 'Dup2',
            'last_name'             => 'User2',
            'email'                 => 'dup@test.com',
            'password'              => 'secret123',
            'password_confirmation' => 'secret123',
        ]);

        $response->assertStatus(422);
    }

    // PUT /api/v1/users/{id}

    public function test_update_modifies_user(): void
    {
        $id = (int) q1("SELECT id FROM users WHERE email = 'admin@test.com'")['id'];

        $response = $this->authed()->put("/api/v1/users/{$id}", [
            'first_name' => 'Updated',
        ]);

        $response->assertStatus(200)
                 ->assertJsonPath('data.first_name', 'Updated');
    }

    public function test_update_returns_404_for_missing_user(): void
    {
        $response = $this->authed()->put('/api/v1/users/99999', ['first_name' => 'X']);

        $response->assertStatus(404);
    }

    // DELETE /api/v1/users/{id}

    public function test_destroy_deletes_user_and_returns_200(): void
    {
        $hash = password_hash('pw', PASSWORD_BCRYPT);
        qi("INSERT INTO users (first_name, last_name, email, password) VALUES ('Del', 'Me', 'delete@test.com', ?)", [$hash]);
        $id = (int) q1("SELECT id FROM users WHERE email = 'delete@test.com'")['id'];

        $response = $this->authed()->delete("/api/v1/users/{$id}");

        $response->assertStatus(200);
    }
}
