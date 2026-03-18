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

$container = new \App\Core\Container();
\App\Facades\App::setInstance($container);

$dispatcher = new \App\Core\EventDispatcher();
\App\Facades\Event::setInstance($dispatcher);
\App\Providers\EventServiceProvider::register($dispatcher);

$queueManager = new \App\Queue\QueueManager(config('queue.default', 'sync'));
\App\Facades\Queue::setInstance($queueManager);

// --- Auth ---
$tokenGuard = new \App\Core\Auth\TokenGuard();
$authManager = new \App\Core\Auth\AuthManager(
    config('auth.defaults.guard', 'api'),
    ['api' => $tokenGuard],
);
\App\Facades\Auth::setInstance($authManager);

// --- Gate ---
$gate = new \App\Core\Auth\Gate();
$gate->register(\App\Models\User::class, new \App\Policies\UserPolicy());
$container->instance(\App\Core\Auth\Gate::class, $gate);

$router = new Router();

$router->addMiddlewareAlias('auth:api', \App\Http\Middleware\AuthMiddleware::class);
$router->addMiddlewareAlias('cors', \App\Http\Middleware\CorsMiddleware::class);

// RateLimitMiddleware — создаётся в P2-T5; фабрика регистрируется заранее.
$router->addMiddlewareFactory('rate_limit', function (string $params): \App\Http\Middleware\RateLimitMiddleware {
    $parts = explode(',', $params);
    if (count($parts) !== 2) {
        throw new \InvalidArgumentException("rate_limit middleware expects 'max,seconds', got: '{$params}'");
    }
    [$max, $decay] = $parts;
    return new \App\Http\Middleware\RateLimitMiddleware((int) $max, (int) $decay);
});

$router->addMiddlewareFactory('can', function (string $params): \App\Http\Middleware\AuthorizationMiddleware {
    return new \App\Http\Middleware\AuthorizationMiddleware($params);
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
if (str_contains($contentType, 'application/json')) {
    $raw  = file_get_contents('php://input');
    $body = json_decode($raw, true);
    if ($raw !== '' && $body === null && json_last_error() !== JSON_ERROR_NONE) {
        ApiResponse::error('Invalid JSON: ' . json_last_error_msg(), 400)->send();
        exit;
    }
    $body = $body ?? [];
} else {
    $body = $_POST;
    if (empty($body) && in_array($method, [Method::PUT, Method::PATCH, Method::DELETE], true)) {
        parse_str(file_get_contents('php://input'), $body);
    }
}
$request = new Request($method, $path, $_GET, $body, $headers);
$requestId = $_SERVER['HTTP_X_REQUEST_ID'] ?? generateUuid();

try {
    $response = Route::dispatch($request);
    $response->withHeader('X-Request-Id', $requestId)->send();
} catch (ValidationException $e) {
    ApiResponse::validationError($e->getErrors())->withHeader('X-Request-Id', $requestId)->send();
} catch (ModelNotFoundException $e) {
    ApiResponse::notFound($e->getMessage())->withHeader('X-Request-Id', $requestId)->send();
} catch (QueryException $e) {
    if ($e->getSqlState() === '23000') {
        ApiResponse::validationError(['email' => 'This email is already in use.'])->withHeader('X-Request-Id', $requestId)->send();
    } else {
        ApiResponse::error('Internal server error.', 500)->withHeader('X-Request-Id', $requestId)->send();
    }
} catch (\TinyRouter\Exception\NotFoundException $e) {
    ApiResponse::notFound('Route not found.')->withHeader('X-Request-Id', $requestId)->send();
} catch (\TinyRouter\Exception\MethodNotAllowedException $e) {
    ApiResponse::error('Method not allowed.', 405)->withHeader('X-Request-Id', $requestId)->send();
} catch (\App\Exceptions\AuthenticationException $e) {
    ApiResponse::unauthorized($e->getMessage())->withHeader('X-Request-Id', $requestId)->send();
} catch (\App\Exceptions\AuthorizationException $e) {
    ApiResponse::error($e->getMessage(), 403)->withHeader('X-Request-Id', $requestId)->send();
} catch (\Throwable $e) {
    \App\Core\Logger::error("[{$requestId}] " . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
    ApiResponse::error('Internal server error.', 500)->withHeader('X-Request-Id', $requestId)->send();
}
