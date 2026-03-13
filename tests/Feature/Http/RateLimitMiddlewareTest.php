<?php

namespace Tests\Feature\Http;

use App\Facades\Cache;
use App\Facades\Config;
use Tests\Support\FeatureTestCase;
use App\Core\Cache\CacheContract;
use App\Core\Cache\Drivers\ArrayDriver;

/** Интеграционные тесты RateLimitMiddleware. */
class RateLimitMiddlewareTest extends FeatureTestCase
{
    private CacheContract $originalDriver;

    /** Подменить драйвер кеша на ArrayDriver и создать тестового пользователя.
     * После подмены перезагружает конфиги, т.к. Config хранит значения в Cache.
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->originalDriver = Cache::getDriver();
        Cache::setDriver(new ArrayDriver());
        Config::load(base_path('config'));

        // Вставить тестового пользователя для попыток входа
        $hash = password_hash('password', PASSWORD_BCRYPT);
        qi("INSERT INTO users (first_name, last_name, email, password) VALUES ('Rate', 'Test', 'rate@test.com', ?)", [$hash]);
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

    /** Выполнить попытку входа тестового пользователя.
     * @return \Tests\Support\TestResponse
     */
    private function attemptLogin(): \Tests\Support\TestResponse
    {
        return $this->client->post('/api/v1/auth/login', [
            'email'    => 'rate@test.com',
            'password' => 'password',
        ]);
    }

    public function test_requests_under_limit_pass_through(): void
    {
        for ($i = 0; $i < 3; $i++) {
            $this->attemptLogin()->assertStatus(200);
        }
    }

    public function test_request_at_limit_passes_through(): void
    {
        // Лимит равен 5 — 5-й запрос должен пройти
        for ($i = 0; $i < 4; $i++) {
            $this->attemptLogin();
        }
        $response = $this->attemptLogin(); // 5-й запрос

        $response->assertStatus(200);
    }

    public function test_request_over_limit_returns_429(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->attemptLogin();
        }

        $response = $this->attemptLogin(); // 6-й запрос

        $response->assertStatus(429)
                 ->assertJsonPath('error.message', 'Too many requests.');
    }

    public function test_429_response_includes_retry_after_header(): void
    {
        for ($i = 0; $i < 6; $i++) {
            $response = $this->attemptLogin();
        }

        $response->assertStatus(429);
        $this->assertNotNull($response->getHeader('Retry-After'));
        $this->assertGreaterThanOrEqual(0, (int) $response->getHeader('Retry-After'));
    }

    public function test_counter_resets_after_ttl_expiry(): void
    {
        // Исчерпать лимит
        for ($i = 0; $i < 6; $i++) {
            $this->attemptLogin();
        }
        $this->attemptLogin()->assertStatus(429);

        // Симулировать истечение TTL через сброс ArrayDriver
        Cache::flush();
        // Конфиги хранятся в Cache — перезагружаем после flush
        Config::load(base_path('config'));

        // Счётчик сброшен — первый запрос проходит
        $this->attemptLogin()->assertStatus(200);
    }
}
