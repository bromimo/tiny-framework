<?php

namespace Tests\Support;

use TinyRouter\Facade\Route;
use TinyRouter\Http\Request;
use TinyRouter\Http\Response;
use TinyRouter\Routing\Router;

/** Тестовое приложение — зеркало bootstrap/app.php без вызова dispatch()->send().
 * Создаёт свежий Router при каждой инициализации и регистрирует маршруты через require.
 * ВАЖНО: синхронизировать с bootstrap/app.php при добавлении новых алиасов или резолверов.
 */
class TestApplication
{
    private Router $router;

    public function __construct()
    {
        $this->router = new Router();

        $this->router->addMiddlewareAlias('auth:api', \App\Http\Middleware\AuthMiddleware::class);

        $this->router->addMiddlewareFactory(
            'rate_limit',
            function (string $params): \App\Http\Middleware\RateLimitMiddleware {
                [$max, $decay] = explode(',', $params);
                return new \App\Http\Middleware\RateLimitMiddleware((int) $max, (int) $decay);
            }
        );

        $this->router->addTypeResolver(
            \App\Abstracts\BaseRequest::class,
            fn(string $type, Request $req) => new $type($req)
        );

        $this->router->addTypeResolver(
            \App\Abstracts\BaseModel::class,
            function (string $type, Request $req) {
                $id = (int) ($req->params['id'] ?? 0);
                if ($id === 0) throw new \App\Exceptions\ModelNotFoundException();
                return $type::findById($id) ?? throw new \App\Exceptions\ModelNotFoundException();
            }
        );

        Route::swap($this->router);

        // require (не require_once) — перерегистрирует маршруты на новом роутере
        require base_path('routes/api_v1.php');
    }

    /** Выполнить запрос и вернуть объект ответа (без send()).
     * @param Request $request
     * @return Response
     */
    public function dispatch(Request $request): Response
    {
        try {
            return $this->router->dispatch($request);
        } catch (\App\Exceptions\ValidationException $e) {
            return \App\Facades\ApiResponse::error($e->getErrors(), 422);
        } catch (\App\Exceptions\ModelNotFoundException $e) {
            return \App\Facades\ApiResponse::notFound($e->getMessage());
        } catch (\App\Exceptions\QueryException $e) {
            if ($e->getSqlState() === '23000') {
                return \App\Facades\ApiResponse::error(['email' => 'This email is already in use.'], 422);
            }
            return \App\Facades\ApiResponse::error('Internal server error.', 500);
        } catch (\TinyRouter\Exception\NotFoundException $e) {
            return \App\Facades\ApiResponse::notFound('Route not found.');
        } catch (\TinyRouter\Exception\MethodNotAllowedException $e) {
            return \App\Facades\ApiResponse::error('Method not allowed.', 405);
        } catch (\Throwable $e) {
            return \App\Facades\ApiResponse::error('Internal server error.', 500);
        }
    }
}
