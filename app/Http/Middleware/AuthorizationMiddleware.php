<?php

namespace App\Http\Middleware;

use App\Facades\Auth;
use TinyRouter\Http\Request;
use TinyRouter\Http\Response;
use App\Exceptions\AuthorizationException;
use TinyRouter\Contract\MiddlewareInterface;

/** Middleware проверки пермишена (грубая проверка на уровне роутов).
 * Проверяет Auth::user()->hasPermission($permission).
 * Не вызывает Policy, не резолвит модель.
 */
class AuthorizationMiddleware implements MiddlewareInterface
{
    /** Создать middleware авторизации.
     * @param string $permission Имя пермишена для проверки.
     */
    public function __construct(
        private readonly string $permission,
    ) {}

    /** Обработать запрос.
     * @param Request $request
     * @param callable $next
     * @return Response
     * @throws AuthorizationException Если пермишен отсутствует.
     */
    public function handle(Request $request, callable $next): Response
    {
        $user = Auth::user();

        if ($user === null || !$user->hasPermission($this->permission)) {
            throw new AuthorizationException('Forbidden.', $this->permission);
        }

        return $next($request);
    }
}
