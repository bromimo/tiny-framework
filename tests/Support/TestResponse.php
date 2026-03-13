<?php

namespace Tests\Support;

use TinyRouter\Http\Response;
use PHPUnit\Framework\Assert;

/** Обёртка над Response с удобными методами для тестирования. */
class TestResponse
{
    private array $decoded;

    public function __construct(private readonly Response $response) {}

    /** Получить статус ответа.
     * @return int
     */
    public function status(): int
    {
        return $this->response->getStatus();
    }

    /** Декодировать тело ответа как JSON.
     * @return array<mixed>
     */
    public function json(): array
    {
        if (!isset($this->decoded)) {
            $this->decoded = json_decode($this->response->getBody(), true) ?? [];
        }
        return $this->decoded;
    }

    /** Получить значение заголовка ответа.
     * @param string $name Имя заголовка (регистр не важен).
     * @return string|null
     */
    public function getHeader(string $name): ?string
    {
        $headers = $this->response->getHeaders();
        return $headers[$name] ?? $headers[strtolower($name)] ?? null;
    }

    /** Проверить HTTP-статус.
     * @param int $code Ожидаемый статус.
     * @return static
     */
    public function assertStatus(int $code): static
    {
        Assert::assertSame($code, $this->status(), "Response status {$this->status()} !== {$code}");
        return $this;
    }

    /** Проверить значение по dot-нотации (числовые сегменты — индексы массива).
     * Пример: 'data.0.id', 'meta.total', 'error.message'
     * @param string $path  Путь в dot-нотации.
     * @param mixed  $value Ожидаемое значение.
     * @return static
     */
    public function assertJsonPath(string $path, mixed $value): static
    {
        $actual = $this->getJsonPath($path);
        Assert::assertSame($value, $actual, "JSON path '{$path}' expected " . json_encode($value) . ', got ' . json_encode($actual));
        return $this;
    }

    /** Проверить наличие ключа по dot-нотации.
     * @param string $path Путь в dot-нотации.
     * @return static
     */
    public function assertJsonHasPath(string $path): static
    {
        $segments = explode('.', $path);
        $data     = $this->json();
        foreach ($segments as $segment) {
            Assert::assertIsArray($data, "JSON path '{$path}' does not exist");
            Assert::assertArrayHasKey($segment, $data, "JSON path '{$path}' does not exist");
            $data = $data[$segment];
        }
        return $this;
    }

    private function getJsonPath(string $path): mixed
    {
        $segments = explode('.', $path);
        $data     = $this->json();
        foreach ($segments as $segment) {
            if (!is_array($data) || !array_key_exists($segment, $data)) {
                return null;
            }
            $data = $data[$segment];
        }
        return $data;
    }
}
