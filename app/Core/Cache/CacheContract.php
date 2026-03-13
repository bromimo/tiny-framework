<?php

namespace App\Core\Cache;

/** Контракт для всех драйверов кеша. */
interface CacheContract
{
    /** Сохранить значение в кеш.
     * @param string $key Ключ.
     * @param mixed $value Значение.
     * @param int $ttl Время жизни в секундах. 0 — бессрочно.
     * @return void
     */
    public function set(string $key, mixed $value, int $ttl = 0): void;

    /** Получить значение из кеша.
     * @param string $key Ключ.
     * @param mixed $default Значение по умолчанию, если ключ не найден.
     * @return mixed
     */
    public function get(string $key, mixed $default = null): mixed;

    /** Проверить наличие ключа в кеше.
     * @param string $key Ключ.
     * @return bool
     */
    public function has(string $key): bool;

    /** Удалить значение из кеша.
     * @param string $key Ключ.
     * @return void
     */
    public function forget(string $key): void;

    /** Очистить весь кеш.
     * @return void
     */
    public function flush(): void;

    /** Атомарно увеличить счётчик на 1.
     * Если ключ отсутствует — инициализировать значением 1 с TTL = $ttl.
     * Если ключ существует — увеличить значение на 1, существующий TTL сохраняется.
     * Параметр $ttl используется ТОЛЬКО при инициализации нового ключа.
     * @param string $key
     * @param int    $ttl Время жизни при создании ключа. 0 — бессрочно.
     * @return int Новое значение счётчика.
     */
    public function increment(string $key, int $ttl = 0): int;
}
