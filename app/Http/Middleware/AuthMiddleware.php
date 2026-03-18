<?php

namespace App\Http\Middleware;

use App\Facades\Auth;
use TinyRouter\Http\Request;
use TinyRouter\Http\Response;
use App\Exceptions\AuthenticationException;
use TinyRouter\Contract\MiddlewareInterface;

/** Middleware аутентификации по Bearer-токену.
 * Делегирует валидацию текущему guard-у через фасад Auth.
 */
class AuthMiddleware implements MiddlewareInterface
{
    /** Обработать запрос — проверить аутентификацию.
     * @param Request $request
     * @param callable $next
     * @return Response
     * @throws AuthenticationException Если токен невалидный или отсутствует.
     */
    public function handle(Request $request, callable $next): Response
    {
        $user = Auth::guard('api')->validate($request);

        if ($user === null) {
            throw new AuthenticationException();
        }

        return $next($request);
    }
}
