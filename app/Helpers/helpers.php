<?php

if (!function_exists('config')) {
    /** Получить значение конфигурации по ключу с точечной нотацией.
     * Первый сегмент ключа — имя файла в директории config/.
     * @param string $key Ключ вида 'file.section.param', например 'auth.token.lifetime'.
     * @param mixed $default Значение по умолчанию, если ключ не найден.
     * @return mixed
     */
    function config(string $key, mixed $default = null): mixed
    {
        return \App\Facades\Config::get($key, $default);
    }
}

if (!function_exists('generateUuid')) {
    /** Сгенерировать уникальный идентификатор UUID v4.
     * @return string UUID в формате xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx.
     * @throws \Random\RandomException Если не удалось получить случайные байты.
     */
    function generateUuid(): string
    {
        $data    = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}

if (!function_exists('q')) {
    /** Синоним DB::query(). Выполнить SELECT-запрос и вернуть все строки результата.
     * @param string $sql SQL-запрос.
     * @param array<int|string, mixed> $params Позиционные или именованные параметры.
     * @return array<int, array<string, mixed>>
     */
    function q(string $sql, array $params = []): array
    {
        return \App\Facades\DB::query($sql, $params);
    }
}

if (!function_exists('q1')) {
    /** Синоним DB::query_once(). Выполнить SELECT-запрос и вернуть только первую строку результата.
     * @param string $sql SQL-запрос.
     * @param array<int|string, mixed> $params Позиционные или именованные параметры.
     * @return array<string, mixed>|null Null если строка не найдена.
     */
    function q1(string $sql, array $params = []): ?array
    {
        return \App\Facades\DB::query_once($sql, $params);
    }
}

if (!function_exists('qi')) {
    /** Синоним DB::query_insert(). Выполнить INSERT, UPDATE или DELETE запрос.
     * @param string $sql SQL-запрос.
     * @param array<int|string, mixed> $params Позиционные или именованные параметры.
     * @return int ID новой записи или количество затронутых строк.
     */
    function qi(string $sql, array $params = []): int
    {
        return \App\Facades\DB::query_insert($sql, $params);
    }
}

if (!function_exists('env')) {
    /** Получить переменную окружения по ключу.
     * @param string $key Имя переменной.
     * @param mixed $default Значение по умолчанию, если ключ не найден.
     * @return mixed
     */
    function env(string $key, mixed $default = null): mixed
    {
        return \App\Facades\Env::get($key, $default);
    }
}

if (!function_exists('stringify')) {
    /** Привести произвольное значение к строке для вывода в лог или отладки.
     * string|int|float — как есть; array|object — JSON; bool — 'true'/'false'; null — 'null'.
     * @param mixed $value Произвольное значение.
     * @return string
     */
    function stringify(mixed $value): string
    {
        return match (true) {
            is_string($value)               => $value,
            is_int($value), is_float($value) => (string)$value,
            is_bool($value)                 => $value ? 'true' : 'false',
            is_null($value)                 => 'null',
            default                         => json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
        };
    }
}

if (!function_exists('logger')) {
    /** Записать сообщение в лог с опциональной нагрузкой.
     * @param string $message Текст сообщения.
     * @param mixed  $payload Дополнительные данные (любой тип, будут приведены к строке).
     * @param string $level   Уровень логирования: info, error, debug.
     */
    function logger(string $message, mixed $payload = null, string $level = 'info'): void
    {
        if ($payload !== null) {
            $message .= ' ' . stringify($payload);
        }

        match ($level) {
            'error' => \App\Core\Logger::error($message),
            'debug' => \App\Core\Logger::debug($message),
            default => \App\Core\Logger::info($message),
        };
    }
}

if (!function_exists('base_path')) {
    /** Вернуть абсолютный путь к корню проекта или файлу внутри него.
     * @param string $path Относительный путь внутри корня проекта (необязательный).
     * @return string Абсолютный путь.
     */
    function base_path(string $path = ''): string
    {
        $base = dirname(__DIR__, 2);
        return $path !== '' ? $base . '/' . ltrim($path, '/') : $base;
    }
}

if (!function_exists('storage_path')) {
    /** Вернуть абсолютный путь к директории storage/ или файлу внутри неё.
     * @param string $path Относительный путь внутри storage/ (необязательный).
     * @return string Абсолютный путь.
     */
    function storage_path(string $path = ''): string
    {
        $base = dirname(__DIR__, 2) . '/storage';
        return $path !== '' ? $base . '/' . ltrim($path, '/') : $base;
    }
}

if (!function_exists('dd')) {
    /** Dump переменных и завершить выполнение.
     * @param mixed ...$vars Произвольные значения для вывода.
     */
    function dd(mixed ...$vars): never
    {
        header('Content-Type: application/json');
        $output = array_map(fn($v) => json_decode(json_encode($v, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), true) ?? stringify($v), $vars);
        echo json_encode(count($output) === 1 ? $output[0] : $output, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
}

if (!function_exists('getBearerToken')) {
    /** Извлечь Bearer-токен из заголовка Authorization текущего запроса.
     * @return string|null Значение токена или null если заголовок отсутствует или имеет неверный формат.
     */
    function getBearerToken(): ?string
    {
        $header = $_SERVER['HTTP_AUTHORIZATION']
            ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION']
            ?? '';

        if (preg_match('/^Bearer\s+(.+)$/i', $header, $matches)) {
            return trim($matches[1]);
        }

        return null;
    }
}

if (!function_exists('success')) {
    /** Вернуть успешный HTTP-ответ 200 с данными.
     * @param \App\Abstracts\BaseResource|array<mixed> $data Данные ответа.
     * @param array<string, mixed>      $meta Метаданные пагинации (необязательно).
     * @return \TinyRouter\Http\Response
     */
    function success(\App\Abstracts\BaseResource|array $data, array $meta = []): \TinyRouter\Http\Response
    {
        return \App\Facades\ApiResponse::ok($data, $meta);
    }
}

if (!function_exists('created')) {
    /** Вернуть HTTP-ответ 201 с созданным ресурсом.
     * @param \App\Abstracts\BaseResource|array<mixed> $data Данные ответа.
     * @return \TinyRouter\Http\Response
     */
    function created(\App\Abstracts\BaseResource|array $data): \TinyRouter\Http\Response
    {
        return \App\Facades\ApiResponse::created($data);
    }
}

if (!function_exists('dispatch')) {
    /** Поставить задачу в очередь.
     * @param \App\Abstracts\Job $job   Задача для выполнения.
     * @param int                $delay Задержка в секундах перед выполнением.
     */
    function dispatch(\App\Abstracts\Job $job, int $delay = 0): void
    {
        if ($delay > 0) {
            \App\Facades\Queue::later($delay, $job);
        } else {
            \App\Facades\Queue::push($job);
        }
    }
}

if (!function_exists('authorize')) {
    /** Авторизовать действие через Gate.
     * @param string $ability Имя действия.
     * @param object|string $model Экземпляр модели или class-string.
     * @return void
     * @throws \App\Exceptions\AuthenticationException Если пользователь не аутентифицирован.
     * @throws \App\Exceptions\AuthorizationException Если действие запрещено.
     */
    function authorize(string $ability, object|string $model): void
    {
        $user = \App\Facades\Auth::user();

        if ($user === null) {
            throw new \App\Exceptions\AuthenticationException();
        }

        $gate = \App\Facades\App::make(\App\Core\Auth\Gate::class);

        if ($gate->denies($user, $ability, $model)) {
            throw new \App\Exceptions\AuthorizationException('Forbidden.', $ability);
        }
    }
}
