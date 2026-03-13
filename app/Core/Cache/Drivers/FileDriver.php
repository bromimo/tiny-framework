<?php

namespace App\Core\Cache\Drivers;

use App\Core\Cache\CacheContract;

/** Драйвер файлового кеша. Данные персистентны между запросами.
 * Каждый ключ хранится в отдельном файле в виде сериализованных данных с временем истечения.
 */
class FileDriver implements CacheContract
{
    private string $directory;

    /** @param string $directory Путь к директории для хранения файлов кеша. */
    public function __construct(string $directory)
    {
        $this->directory = rtrim($directory, '/\\');

        if (!is_dir($this->directory)) {
            mkdir($this->directory, 0755, true);
        }
    }

    /** @inheritDoc */
    public function set(string $key, mixed $value, int $ttl = 0): void
    {
        $payload = [
            'expires_at' => $ttl > 0 ? time() + $ttl : 0,
            'value'      => $value,
        ];

        file_put_contents($this->path($key), serialize($payload), LOCK_EX);
    }

    /** @inheritDoc */
    public function get(string $key, mixed $default = null): mixed
    {
        $payload = $this->read($key);

        if ($payload === null) {
            return $default;
        }

        return $payload['value'];
    }

    /** @inheritDoc */
    public function has(string $key): bool
    {
        return $this->read($key) !== null;
    }

    /** @inheritDoc */
    public function forget(string $key): void
    {
        $path = $this->path($key);

        if (file_exists($path)) {
            unlink($path);
        }
    }

    /** @inheritDoc */
    public function flush(): void
    {
        foreach (glob($this->directory . '/*.cache') as $file) {
            unlink($file);
        }
    }

    /** @inheritDoc */
    public function increment(string $key, int $ttl = 0): int
    {
        $path = $this->path($key);
        $fd   = fopen($path, 'c+');

        if ($fd === false) {
            $this->set($key, 1, $ttl);
            return 1;
        }

        flock($fd, LOCK_EX);

        $content = stream_get_contents($fd);
        $payload = !empty($content) ? unserialize($content) : null;

        if ($payload === null || ($payload['expires_at'] > 0 && $payload['expires_at'] < time())) {
            $payload = [
                'expires_at' => $ttl > 0 ? time() + $ttl : 0,
                'value'      => 1,
            ];
        } else {
            $payload['value'] = (int) $payload['value'] + 1;
        }

        rewind($fd);
        ftruncate($fd, 0);
        fwrite($fd, serialize($payload));
        flock($fd, LOCK_UN);
        fclose($fd);

        return (int) $payload['value'];
    }

    /** Прочитать и десериализовать файл кеша. Возвращает null если файл не найден или истёк.
     * @param string $key
     * @return array{expires_at: int, value: mixed}|null
     */
    private function read(string $key): ?array
    {
        $path = $this->path($key);

        if (!file_exists($path)) {
            return null;
        }

        $payload = unserialize(file_get_contents($path));

        if ($payload['expires_at'] > 0 && $payload['expires_at'] < time()) {
            unlink($path);
            return null;
        }

        return $payload;
    }

    /** Вернуть путь к файлу кеша для заданного ключа.
     * @param string $key
     * @return string
     */
    private function path(string $key): string
    {
        return $this->directory . '/' . md5($key) . '.cache';
    }
}
