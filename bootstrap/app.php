<?php

use App\Facades\Env;
use App\Facades\Cache;
use App\Facades\Config;
use TinyRouter\Http\Method;
use App\Abstracts\BaseModel;
use App\Facades\ApiResponse;
use TinyRouter\Facade\Route;
use TinyRouter\Http\Request;
use App\Abstracts\BaseRequest;
use TinyRouter\Routing\Router;
use App\Exceptions\ValidationException;
use App\Exceptions\ModelNotFoundException;
use App\Exceptions\QueryException;

Env::load(__DIR__ . '/..');
Cache::init();
Config::load(__DIR__ . '/../config');

$router = new Router();

$router->addMiddlewareAlias('auth:api', \App\Http\Middleware\AuthMiddleware::class);

$router->addMiddlewareFactory('rate_limit', function (string $params): \App\Http\Middleware\RateLimitMiddleware {
    [$max, $decay] = explode(',', $params);
    return new \App\Http\Middleware\RateLimitMiddleware((int) $max, (int) $decay);
});

$router->addTypeResolver(
    BaseRequest::class,
    fn(string $type, Request $req) => new $type($req)
);

$router->addTypeResolver(
    BaseModel::class,
    function (string $type, Request $req) {
        /** @var class-string<BaseModel> $type */
        $id = (int)($req->params['id'] ?? 0);
        if ($id === 0) {
            throw new ModelNotFoundException();
        }

        return $type::findById($id) ?? throw new ModelNotFoundException();
    }
);

Route::swap($router);

require_once __DIR__ . '/../routes/api_v1.php';

$method = Method::fromString($_SERVER['REQUEST_METHOD'] ?? 'GET');
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
$headers = [];

foreach ($_SERVER as $key => $value) {
    if (str_starts_with($key, 'HTTP_')) {
        $headers[strtolower(str_replace('_', '-', substr($key, 5)))] = $value;
    }
}

$contentType = $_SERVER['CONTENT_TYPE'] ?? '';
$body = str_contains($contentType, 'application/json')
    ? (json_decode(file_get_contents('php://input'), true) ?? [])
    : $_POST;
$request = new Request($method, $path, $_GET, $body, $headers);

try {
    Route::dispatch($request)->send();
} catch (ValidationException $e) {
    ApiResponse::error($e->getErrors(), 422)->send();
} catch (ModelNotFoundException $e) {
    ApiResponse::notFound($e->getMessage())->send();
} catch (QueryException $e) {
    if ($e->getSqlState() === '23000') {
        ApiResponse::error(['email' => 'This email is already in use.'], 422)->send();
    } else {
        ApiResponse::error('Internal server error.', 500)->send();
    }
} catch (\TinyRouter\Exception\NotFoundException $e) {
    ApiResponse::notFound('Route not found.')->send();
} catch (\TinyRouter\Exception\MethodNotAllowedException $e) {
    ApiResponse::error('Method not allowed.', 405)->send();
} catch (\Throwable $e) {
    ApiResponse::error('Internal server error.', 500)->send();
}
