<?php

namespace App\Http\Middleware;

use TinyRouter\Http\Request;
use TinyRouter\Http\Response;
use TinyRouter\Contract\MiddlewareInterface;

/** Middleware добавления CORS-заголовков. */
class CorsMiddleware implements MiddlewareInterface
{
    /** Добавить CORS-заголовки к ответу.
     * @param Request  $request
     * @param callable $next
     * @return Response
     */
    public function handle(Request $request, callable $next): Response
    {
        $origin  = config('cors.allowed_origins', '*');
        $methods = config('cors.allowed_methods', 'GET, POST, PUT, DELETE, OPTIONS');
        $headers = config('cors.allowed_headers', 'Content-Type, Authorization');
        $maxAge  = (string) config('cors.max_age', 86400);

        // Preflight (OPTIONS) — ответить сразу
        if ($request->method->value === 'OPTIONS') {
            return (new Response('', 204))
                ->withHeader('Access-Control-Allow-Origin', $origin)
                ->withHeader('Access-Control-Allow-Methods', $methods)
                ->withHeader('Access-Control-Allow-Headers', $headers)
                ->withHeader('Access-Control-Max-Age', $maxAge);
        }

        $response = $next($request);

        return $response
            ->withHeader('Access-Control-Allow-Origin', $origin)
            ->withHeader('Access-Control-Allow-Methods', $methods)
            ->withHeader('Access-Control-Allow-Headers', $headers);
    }
}
