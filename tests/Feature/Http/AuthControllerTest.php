<?php

namespace Tests\Feature\Http;

use Tests\Support\FeatureTestCase;

/** Интеграционные тесты контроллера аутентификации. */
class AuthControllerTest extends FeatureTestCase
{
    /** Создать тестового пользователя в БД.
     * @param string $email
     * @param string $password
     * @return void
     */
    private function createUser(string $email = 'test@example.com', string $password = 'password123'): void
    {
        $hash = password_hash($password, PASSWORD_BCRYPT);
        qi("INSERT INTO users (first_name, last_name, email, password) VALUES ('Test', 'User', ?, ?)", [$email, $hash]);
    }

    // POST /api/v1/auth/login

    public function test_login_with_valid_credentials_returns_token(): void
    {
        $this->createUser();

        $response = $this->client->post('/api/v1/auth/login', [
            'email'    => 'test@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
                 ->assertJsonHasPath('data.token');
    }

    public function test_login_with_wrong_password_returns_401(): void
    {
        $this->createUser();

        $response = $this->client->post('/api/v1/auth/login', [
            'email'    => 'test@example.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(401);
    }

    public function test_login_with_unknown_email_returns_401(): void
    {
        $response = $this->client->post('/api/v1/auth/login', [
            'email'    => 'nobody@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(401);
    }

    public function test_login_with_missing_fields_returns_422(): void
    {
        $response = $this->client->post('/api/v1/auth/login', []);

        $response->assertStatus(422);
    }

    // POST /api/v1/auth/logout

    public function test_logout_with_valid_token_returns_200(): void
    {
        $this->createUser();

        $loginResponse = $this->client->post('/api/v1/auth/login', [
            'email'    => 'test@example.com',
            'password' => 'password123',
        ]);

        $token = $loginResponse->json()['data']['token'];

        $response = $this->client->withToken($token)->post('/api/v1/auth/logout');

        $response->assertStatus(200);
    }

    public function test_logout_with_invalid_token_returns_401(): void
    {
        $response = $this->client->withToken('invalid-token-xyz')->post('/api/v1/auth/logout');

        $response->assertStatus(401);
    }

    public function test_logout_without_token_returns_401(): void
    {
        $response = $this->client->post('/api/v1/auth/logout');

        $response->assertStatus(401);
    }
}
