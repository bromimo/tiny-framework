<?php

namespace Tests\Feature\Http;

use Tests\Support\FeatureTestCase;

/** Интеграционные тесты AuthMiddleware. */
class AuthMiddlewareTest extends FeatureTestCase
{
    public function test_request_without_token_returns_401(): void
    {
        $response = $this->client->get('/api/v1/users');

        $response->assertStatus(401);
    }

    public function test_request_with_invalid_token_returns_401(): void
    {
        $response = $this->client->withToken('not-a-real-token')->get('/api/v1/users');

        $response->assertStatus(401);
    }

    public function test_request_with_expired_token_returns_401(): void
    {
        $hash = password_hash('pw', PASSWORD_BCRYPT);
        qi("INSERT INTO users (first_name, last_name, email, password, role_id) VALUES ('A', 'B', 'expired@test.com', ?, 1)", [$hash]);
        $userId = (int) q1("SELECT id FROM users WHERE email = 'expired@test.com'")['id'];

        // Вставить токен с истёкшим сроком действия
        qi("INSERT INTO tokens (user_id, token, expires_at) VALUES (?, 'expired-token-abc', DATE_SUB(NOW(), INTERVAL 1 HOUR))", [$userId]);

        $response = $this->client->withToken('expired-token-abc')->get('/api/v1/users');

        $response->assertStatus(401);
    }

    public function test_request_with_valid_token_passes_through(): void
    {
        $hash = password_hash('pw123', PASSWORD_BCRYPT);
        qi("INSERT INTO users (first_name, last_name, email, password, role_id) VALUES ('C', 'D', 'valid@test.com', ?, 1)", [$hash]);

        $loginResponse = $this->client->post('/api/v1/auth/login', [
            'email'    => 'valid@test.com',
            'password' => 'pw123',
        ]);
        $token = $loginResponse->json()['data']['token'];

        $response = $this->client->withToken($token)->get('/api/v1/users');

        $response->assertStatus(200);
    }
}
