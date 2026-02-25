<?php

namespace App\Http\Responses;

use TinyRouter\Http\Response;

class ApiResponse
{
    public static function ok(mixed $data): Response
    {
        return self::json(['data' => $data], 200);
    }

    public static function created(mixed $data): Response
    {
        return self::json(['data' => $data], 201);
    }

    public static function error(mixed $message, int $status = 400): Response
    {
        return self::json(['error' => $message], $status);
    }

    public static function notFound(string $message = 'Not found'): Response
    {
        return self::error($message, 404);
    }

    public static function unauthorized(string $message = 'Unauthorized'): Response
    {
        return self::error($message, 401);
    }

    private static function json(array $payload, int $status): Response
    {
        return (new Response(json_encode($payload), $status))
            ->withHeader('Content-Type', 'application/json');
    }
}
