<?php

namespace App\Facades;

use App\Core\Cache\CacheContract;
use App\Core\Cache\Drivers\FileDriver;
use App\Core\Cache\Drivers\ArrayDriver;
use App\Core\Cache\Drivers\MemcachedDriver;

/** Статический фасад над драйвером кеша.
 * Инициализируется один раз при старте приложения через {@see init()}.
 */
class Cache
{
    /** @var CacheContract|null Активный драйвер. */
    private static ?CacheContract $driver = null;

    /** Инициализировать кеш драйвером из config/cache.php.
     * Читает конфиг напрямую через require — вызывать до config_preload() в bootstrap.
     * @return void
     */
    public static function init(): void
    {
        $config = require __DIR__ . '/../../config/cache.php';

        self::$driver = match ($config['default']) {
            'file'      => new FileDriver($config['drivers']['file']['path']),
            'memcached' => new MemcachedDriver(
                $config['drivers']['memcached']['host'],
                (int) $config['drivers']['memcached']['port'],
            ),
            default => new ArrayDriver(),
        };
    }

    /** Сохранить значение в кеш.
     * @param string $key Ключ.
     * @param mixed $value Значение.
     * @param int $ttl Время жизни в секундах. 0 — бессрочно.
     * @return void
     */
    public static function set(string $key, mixed $value, int $ttl = 0): void
    {
        self::driver()->set($key, $value, $ttl);
    }

    /** Получить значение из кеша.
     * @param string $key Ключ.
     * @param mixed $default Значение по умолчанию, если ключ не найден.
     * @return mixed
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        return self::driver()->get($key, $default);
    }

    /** Проверить наличие ключа в кеше.
     * @param string $key Ключ.
     * @return bool
     */
    public static function has(string $key): bool
    {
        return self::driver()->has($key);
    }

    /** Удалить значение из кеша.
     * @param string $key Ключ.
     * @return void
     */
    public static function forget(string $key): void
    {
        self::driver()->forget($key);
    }

    /** Очистить весь кеш. Используется в тестах.
     * @return void
     */
    public static function flush(): void
    {
        self::driver()->flush();
    }

    /** Атомарно увеличить счётчик.
     * @param string $key
     * @param int    $ttl Время жизни при создании ключа. 0 — бессрочно. Если ключ существует — существующий TTL сохраняется.
     * @return int Новое значение счётчика.
     */
    public static function increment(string $key, int $ttl = 0): int
    {
        return self::driver()->increment($key, $ttl);
    }

    /** Вернуть активный драйвер кеша.
     * @return CacheContract
     */
    public static function getDriver(): CacheContract
    {
        return self::driver();
    }

    /** Установить драйвер кеша. Используется в тестах для подмены драйвера.
     * @param CacheContract $driver
     * @return void
     */
    public static function setDriver(CacheContract $driver): void
    {
        self::$driver = $driver;
    }

    /** Вернуть активный драйвер, инициализируя ArrayDriver если init() не был вызван.
     * @return CacheContract
     */
    private static function driver(): CacheContract
    {
        if (self::$driver === null) {
            self::$driver = new ArrayDriver();
        }

        return self::$driver;
    }
}
