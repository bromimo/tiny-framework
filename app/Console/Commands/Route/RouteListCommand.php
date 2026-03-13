<?php

namespace App\Console\Commands\Route;

use TinyRouter\Facade\Route;
use TinyRouter\Routing\Router;
use App\Abstracts\BaseCommand;
use App\Abstracts\BaseModel;
use App\Abstracts\BaseRequest;
use TinyRouter\Http\Request;

/** Выводит список маршрутов в стиле Laravel 10. */
class RouteListCommand extends BaseCommand
{
    private const METHOD_WIDTH = 16;
    private const BLUE         = "\033[34m";
    private const CYAN         = "\033[36m";

    private const METHOD_COLORS = [
        'GET'     => "\033[32m",
        'POST'    => "\033[33m",
        'PUT'     => "\033[34m",
        'PATCH'   => "\033[35m",
        'DELETE'  => "\033[31m",
        'OPTIONS' => "\033[90m",
    ];

    public static string $name = 'route:list';

    /** Вернуть краткое описание команды. */
    public function description(): string
    {
        return 'Показать список всех зарегистрированных маршрутов.';
    }

    /** Выполнить команду.
     * @param array<int, string> $args
     */
    public function handle(array $args): void
    {
        $this->bootRoutes();

        $routes = Route::routes();

        if (empty($routes)) {
            echo self::YELLOW . '  No routes registered.' . self::RESET . PHP_EOL;
            return;
        }

        $termWidth = $this->terminalWidth();

        echo PHP_EOL;

        foreach ($routes as $route) {
            $middlewares = array_unique([...$route->groupMiddlewares, ...$route->getRouteMiddlewares()]);

            $this->printRoute(
                method:     $route->method->value,
                uri:        ltrim($route->pattern, '/'),
                name:       $route->getName() ?? '',
                middleware: implode(', ', $middlewares),
                handler:    $this->formatHandler($route->handler),
                width:      $termWidth,
            );
        }

        $total   = 'Showing [' . count($routes) . '] routes';
        $padding = max(0, $termWidth - strlen($total) - 2);
        echo PHP_EOL . str_repeat(' ', $padding) . self::BLUE . $total . self::RESET . PHP_EOL . PHP_EOL;
    }

    /** Вывести одну строку маршрута.
     * @param string $method
     * @param string $uri
     * @param string $name
     * @param string $middleware
     * @param string $handler
     * @param int    $width Ширина терминала.
     */
    private function printRoute(string $method, string $uri, string $name, string $middleware, string $handler, int $width): void
    {
        $color     = self::METHOD_COLORS[$method] ?? self::GRAY;
        $methodStr = $color . self::BOLD . str_pad($method, self::METHOD_WIDTH) . self::RESET;

        $rightParts        = [];
        $rightPartsVisible = [];

        if ($name !== '') {
            $rightParts[]        = self::BLUE . $name . self::RESET;
            $rightPartsVisible[] = $name;
        }

        if ($middleware !== '') {
            $rightParts[]        = self::BLUE . $middleware . self::RESET;
            $rightPartsVisible[] = $middleware;
        }

        $rightParts[]        = self::BLUE . $handler . self::RESET;
        $rightPartsVisible[] = $handler;

        $separator        = ' ' . self::BLUE . '›' . self::RESET . ' ';
        $separatorVisible = ' › ';

        $right        = implode($separator, $rightParts);
        $rightVisible = implode($separatorVisible, $rightPartsVisible);

        // 2 indent + METHOD_WIDTH + uri + space + dots + space + right = width
        $dotsLen = max(3, $width - 4 - self::METHOD_WIDTH - strlen($uri) - 2 - mb_strlen($rightVisible));
        $dots    = self::BLUE . str_repeat('.', $dotsLen) . self::RESET;

        $uriStr = preg_replace('/\{[^}]+\}/', self::YELLOW . '$0' . self::RESET, $uri);

        echo '  ' . $methodStr . $uriStr . ' ' . $dots . ' ' . $right . PHP_EOL;
    }

    /** Привести handler к строке вида Namespace\ClassName@method.
     * @param mixed $handler
     * @return string
     */
    private function formatHandler(mixed $handler): string
    {
        if ($handler instanceof \Closure) {
            return 'Closure';
        }

        if (is_array($handler) && count($handler) === 2) {
            [$class, $method] = $handler;
            $className = is_object($class) ? get_class($class) : $class;
            return $className . '@' . $method;
        }

        if (is_string($handler)) {
            return $handler;
        }

        return (string) $handler;
    }

    /** Определить ширину терминала.
     * @return int
     */
    private function terminalWidth(): int
    {
        // Unix
        if (PHP_OS_FAMILY !== 'Windows') {
            $cols = (int) shell_exec('tput cols 2>/dev/null');
            if ($cols > 0) {
                return $cols;
            }
        }

        // Windows: parse `mode con`
        $output = shell_exec('mode con 2>nul');
        if ($output && preg_match('/Columns[:\s]+(\d+)/i', $output, $m)) {
            return (int) $m[1];
        }

        // Fallback: env var or default
        $cols = (int) getenv('COLUMNS');
        return $cols > 0 ? $cols : 220;
    }

    /** Загрузить роутер и маршруты без HTTP-цикла. */
    private function bootRoutes(): void
    {
        $router = new Router();
        $router->addMiddlewareAlias('auth:api', \App\Http\Middleware\AuthMiddleware::class);
        $router->addTypeResolver(BaseRequest::class, fn(string $type, Request $req) => new $type($req));
        $router->addTypeResolver(BaseModel::class,   fn(string $type, Request $req) => new $type([]));

        Route::swap($router);

        require base_path('routes/api_v1.php');
    }
}
