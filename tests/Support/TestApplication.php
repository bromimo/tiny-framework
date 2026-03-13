<?php

namespace Tests\Support;

use App\Facades\ApiResponse;
use TinyRouter\Facade\Route;
use TinyRouter\Http\Request;
use TinyRouter\Http\Response;
use App\Abstracts\BaseModel;
use App\Abstracts\BaseRequest;
use TinyRouter\Routing\Router;
use App\Exceptions\QueryException;
use App\Exceptions\ValidationException;
use App\Exceptions\ModelNotFoundException;
use App\Http\Middleware\AuthMiddleware;
use App\Http\Middleware\RateLimitMiddleware;
use TinyRouter\Exception\NotFoundException;
use TinyRouter\Exception\MethodNotAllowedException;

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

        $this->router->addMiddlewareAlias('auth:api', AuthMiddleware::class);

        $this->router->addMiddlewareFactory(
            'rate_limit',
            function (string $params): RateLimitMiddleware {
                $parts = explode(',', $params);
                if (count($parts) !== 2) {
                    throw new \InvalidArgumentException("rate_limit middleware expects 'max,seconds', got: '{$params}'");
                }
                [$max, $decay] = $parts;
                return new RateLimitMiddleware((int) $max, (int) $decay);
            }
        );

        $this->router->addTypeResolver(
            BaseRequest::class,
            fn(string $type, Request $req) => new $type($req)
        );

        $this->router->addTypeResolver(
            BaseModel::class,
            function (string $type, Request $req) {
                $id = (int) ($req->params['id'] ?? 0);
                if ($id === 0) throw new ModelNotFoundException();
                return $type::findById($id) ?? throw new ModelNotFoundException();
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
        } catch (ValidationException $e) {
            return ApiResponse::error($e->getErrors(), 422);
        } catch (ModelNotFoundException $e) {
            return ApiResponse::notFound($e->getMessage());
        } catch (QueryException $e) {
            if ($e->getSqlState() === '23000') {
                return ApiResponse::error(['email' => 'This email is already in use.'], 422);
            }
            return ApiResponse::error('Internal server error.', 500);
        } catch (NotFoundException $e) {
            return ApiResponse::notFound('Route not found.');
        } catch (MethodNotAllowedException $e) {
            return ApiResponse::error('Method not allowed.', 405);
        } catch (\Throwable $e) {
            return ApiResponse::error('Internal server error.', 500);
        }
    }
}
