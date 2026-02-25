<?php

namespace App\Http\Middleware;

use App\Http\Responses\ApiResponse;
use App\Models\Token;
use App\Support\Logger;
use TinyRouter\Contract\MiddlewareInterface;
use TinyRouter\Http\Request;
use TinyRouter\Http\Response;

class AuthMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, callable $next): Response
    {
        $token = getBearerToken();

        if ($token === null) {
            return ApiResponse::unauthorized('No token provided.');
        }

        $record = Token::findValid($token);

        if ($record === null) {
            Logger::info("Invalid or expired token attempt: {$token}");
            return ApiResponse::unauthorized('Invalid or expired token.');
        }

        return $next($request);
    }
}
