<?php

namespace Tests\Support;

use TinyRouter\Http\Method;
use TinyRouter\Http\Request;

/** HTTP-клиент для интеграционных тестов.
 * Строит Request, диспетчеризует через TestApplication, возвращает TestResponse.
 */
class TestClient
{
    private array $headers = [];
    private TestApplication $app;

    public function __construct()
    {
        $this->app = new TestApplication();
    }

    /** Добавить Bearer-токен к следующим запросам.
     * @param string $token
     * @return static
     */
    public function withToken(string $token): static
    {
        $clone = clone $this;
        $clone->headers['authorization'] = "Bearer {$token}";
        return $clone;
    }

    /** GET-запрос.
     * @param string               $uri
     * @param array<string, mixed> $query
     * @return TestResponse
     */
    public function get(string $uri, array $query = []): TestResponse
    {
        return $this->send('GET', $uri, query: $query);
    }

    /** POST-запрос.
     * @param string               $uri
     * @param array<string, mixed> $body
     * @return TestResponse
     */
    public function post(string $uri, array $body = []): TestResponse
    {
        return $this->send('POST', $uri, body: $body);
    }

    /** PUT-запрос.
     * @param string               $uri
     * @param array<string, mixed> $body
     * @return TestResponse
     */
    public function put(string $uri, array $body = []): TestResponse
    {
        return $this->send('PUT', $uri, body: $body);
    }

    /** DELETE-запрос.
     * @param string $uri
     * @return TestResponse
     */
    public function delete(string $uri): TestResponse
    {
        return $this->send('DELETE', $uri);
    }

    private function send(string $method, string $uri, array $body = [], array $query = []): TestResponse
    {
        $request = new Request(
            method:  Method::fromString($method),
            path:    $uri,
            query:   $query,
            body:    $body,
            headers: $this->headers,
        );

        return new TestResponse($this->app->dispatch($request));
    }
}
