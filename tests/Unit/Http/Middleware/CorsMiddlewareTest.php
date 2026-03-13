<?php

namespace Tests\Unit\Http\Middleware;

use PHPUnit\Framework\TestCase;
use TinyRouter\Http\Method;
use TinyRouter\Http\Request;
use TinyRouter\Http\Response;
use App\Http\Middleware\CorsMiddleware;

/** Тесты CORS middleware. */
class CorsMiddlewareTest extends TestCase
{
    private function makeRequest(string $method = 'GET'): Request
    {
        return new Request(Method::fromString($method), '/test', [], [], []);
    }

    private function next(): callable
    {
        return fn(Request $req) => new Response('ok', 200);
    }

    public function test_adds_cors_headers_to_response(): void
    {
        $middleware = new CorsMiddleware();
        $response  = $middleware->handle($this->makeRequest(), $this->next());

        $this->assertSame(200, $response->getStatus());
        $headers = $response->getHeaders();
        $this->assertSame('*', $headers['Access-Control-Allow-Origin']);
        $this->assertArrayHasKey('Access-Control-Allow-Methods', $headers);
        $this->assertArrayHasKey('Access-Control-Allow-Headers', $headers);
    }

    public function test_preflight_returns_204(): void
    {
        $middleware = new CorsMiddleware();
        $response  = $middleware->handle($this->makeRequest('OPTIONS'), $this->next());

        $this->assertSame(204, $response->getStatus());
        $headers = $response->getHeaders();
        $this->assertSame('*', $headers['Access-Control-Allow-Origin']);
        $this->assertNotEmpty($headers['Access-Control-Max-Age']);
    }

    public function test_preflight_does_not_call_next(): void
    {
        $called    = false;
        $next      = function (Request $req) use (&$called) {
            $called = true;
            return new Response('ok', 200);
        };
        $middleware = new CorsMiddleware();
        $middleware->handle($this->makeRequest('OPTIONS'), $next);

        $this->assertFalse($called);
    }
}
