<?php

namespace App\Core\Cache\Drivers;

use Memcached;
use App\Core\Cache\CacheContract;

/** Драйвер кеша на основе Memcached. Данные персистентны между запросами. */
class MemcachedDriver implements CacheContract
{
    private Memcached $memcached;

    /** @param string $host Хост Memcached-сервера.
     * @param int $port Порт Memcached-сервера.
     */
    public function __construct(string $host, int $port)
    {
        $this->memcached = new Memcached();
        $this->memcached->setOption(Memcached::OPT_BINARY_PROTOCOL, true);
        $this->memcached->addServer($host, $port);
    }

    /** @inheritDoc */
    public function set(string $key, mixed $value, int $ttl = 0): void
    {
        $this->memcached->set($key, $value, $ttl);
    }

    /** @inheritDoc */
    public function get(string $key, mixed $default = null): mixed
    {
        $value = $this->memcached->get($key);

        if ($this->memcached->getResultCode() !== Memcached::RES_SUCCESS) {
            return $default;
        }

        return $value;
    }

    /** @inheritDoc */
    public function has(string $key): bool
    {
        $this->memcached->get($key);
        return $this->memcached->getResultCode() !== Memcached::RES_NOTFOUND;
    }

    /** @inheritDoc */
    public function forget(string $key): void
    {
        $this->memcached->delete($key);
    }

    /** @inheritDoc */
    public function flush(): void
    {
        $this->memcached->flush();
    }

    /** @inheritDoc */
    public function increment(string $key, int $ttl = 0): int
    {
        // Четвёртый аргумент: initial_value=1, expiry=$ttl — атомарная инициализация при отсутствии ключа.
        // При ошибке сервера Memcached::increment() возвращает false — возвращаем 1 как безопасный fallback.
        // Для rate-limiting это означает «первая попытка» вместо блокировки, что предпочтительнее отказа сервиса.
        $result = $this->memcached->increment($key, 1, 1, $ttl);
        return $result === false ? 1 : (int) $result;
    }
}
