<?php

namespace App\Core\Cache\Drivers;

use App\Core\Cache\CacheContract;

/** Драйвер кеша в памяти. Данные живут только в рамках текущего запроса.
 * Поддерживает TTL через хранение времени истечения рядом со значением.
 */
class ArrayDriver implements CacheContract
{
    /** @var array<string, array{value: mixed, expires_at: int|null}> Хранилище значений. */
    private array $store = [];

    /** @inheritDoc */
    public function set(string $key, mixed $value, int $ttl = 0): void
    {
        $this->store[$key] = [
            'value'      => $value,
            'expires_at' => $ttl > 0 ? time() + $ttl : null,
        ];
    }

    /** @inheritDoc */
    public function get(string $key, mixed $default = null): mixed
    {
        if (!array_key_exists($key, $this->store)) {
            return $default;
        }

        $item = $this->store[$key];

        if ($item['expires_at'] !== null && time() > $item['expires_at']) {
            unset($this->store[$key]);
            return $default;
        }

        return $item['value'];
    }

    /** @inheritDoc */
    public function has(string $key): bool
    {
        return $this->get($key) !== null || (
            array_key_exists($key, $this->store)
            && ($this->store[$key]['expires_at'] === null || time() <= $this->store[$key]['expires_at'])
            && $this->store[$key]['value'] === null
        );
    }

    /** @inheritDoc */
    public function forget(string $key): void
    {
        unset($this->store[$key]);
    }

    /** @inheritDoc */
    public function flush(): void
    {
        $this->store = [];
    }

    /** @inheritDoc */
    public function increment(string $key, int $ttl = 0): int
    {
        if (array_key_exists($key, $this->store)) {
            $item = $this->store[$key];

            if ($item['expires_at'] !== null && time() > $item['expires_at']) {
                // Expired — init as new key
                $this->store[$key] = [
                    'value'      => 1,
                    'expires_at' => $ttl > 0 ? time() + $ttl : null,
                ];
                return 1;
            }

            // Preserve existing expires_at, increment value
            $this->store[$key]['value'] = (int) $item['value'] + 1;
            return (int) $this->store[$key]['value'];
        }

        $this->store[$key] = [
            'value'      => 1,
            'expires_at' => $ttl > 0 ? time() + $ttl : null,
        ];
        return 1;
    }
}
