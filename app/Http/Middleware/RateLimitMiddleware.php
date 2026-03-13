<?php

namespace App\Http\Middleware;

use App\Facades\Cache;
use App\Facades\ApiResponse;
use TinyRouter\Http\Request;
use TinyRouter\Http\Response;
use TinyRouter\Contract\MiddlewareInterface;

/** Middleware ограничения количества запросов (rate limiting).
 * Параметры передаются через конструктор: максимум попыток и окно в секундах.
 */
class RateLimitMiddleware implements MiddlewareInterface
{
    /** Инициализировать middleware с параметрами ограничения запросов.
     * @param int $maxAttempts  Максимальное количество запросов в окне.
     * @param int $decaySeconds Размер окна в секундах.
     */
    public function __construct(
        private readonly int $maxAttempts,
        private readonly int $decaySeconds,
    ) {}

    /** Обработать входящий запрос.
     * @param Request  $request
     * @param callable $next
     * @return Response
     */
    public function handle(Request $request, callable $next): Response
    {
        $remoteAddr     = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $trustedProxies = config('auth.trusted_proxies', []);
        $ip             = (in_array($remoteAddr, $trustedProxies, true) && isset($_SERVER['HTTP_X_FORWARDED_FOR']))
            ? trim(explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0])
            : $remoteAddr;
        $method  = $request->method->value;
        $path    = $request->path;
        $key     = "rate_limit:{$ip}:{$method}:{$path}";
        $expires = "rate_limit:expires:{$ip}:{$method}:{$path}";

        $counter = Cache::increment($key, $this->decaySeconds);

        if ($counter === 1) {
            Cache::set($expires, time() + $this->decaySeconds, $this->decaySeconds);
        }

        if ($counter > $this->maxAttempts) {
            $expiresAt  = Cache::get($expires) ?? (time() + $this->decaySeconds);
            $retryAfter = max(0, (int) ($expiresAt - time()));

            return ApiResponse::error('Too many requests.', 429)
                ->withHeader('Retry-After', (string) $retryAfter);
        }

        return $next($request);
    }
}
