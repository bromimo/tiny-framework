<?php

namespace App\Http\Middleware;

use App\Core\Logger;
use App\Models\Token;
use App\Facades\ApiResponse;
use TinyRouter\Http\Request;
use TinyRouter\Http\Response;
use TinyRouter\Contract\MiddlewareInterface;

/** Middleware аутентификации: проверяет bearer-токен в заголовке Authorization. */
class AuthMiddleware implements MiddlewareInterface
{
    /** Проверить токен и передать запрос дальше или вернуть 401.
     * @param Request  $request
     * @param callable $next
     * @return Response
     */
    public function handle(Request $request, callable $next): Response
    {
        $token = getBearerToken();

        if ($token === null) {
            return ApiResponse::unauthorized('No token provided.');
        }

        $record = Token::findValid($token);

        if ($record === null) {
            Logger::info('Invalid or expired token attempt: ' . substr($token, 0, 8) . '...');
            return ApiResponse::unauthorized('Invalid or expired token.');
        }

        return $next($request);
    }
}
